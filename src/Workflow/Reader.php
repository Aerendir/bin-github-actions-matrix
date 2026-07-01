<?php

declare(strict_types=1);

/*
 * This file is part of the Aerendir GitHub Actions Matrix.
 *
 * Copyright (c) Adamo Aerendir Crespi <aerendir@serendipityhq.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Aerendir\Bin\GitHubActionsMatrix\Workflow;

use Aerendir\Bin\GitHubActionsMatrix\ValueObject\Job;
use Aerendir\Bin\GitHubActionsMatrix\ValueObject\JobsCollection;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class Reader
{
    public function __construct(private readonly Finder $finder = new Finder(), private readonly NonDerivableContextDetector $nonDerivableContextDetector = new NonDerivableContextDetector())
    {
    }

    /**
     * @param array<array-key, string> $possibleFolders explicit folders to look into, in priority order;
     *                                                  the Finder appends the package fallbacks after them
     * @param array<array-key, string> $ignoredJobs     job ids to exclude from the computed set entirely
     * @param array<array-key, string> $requiredChecks  external / non-workflow checks to preserve as
     *                                                  bare-name required contexts (e.g. codecov)
     */
    public function read(array $possibleFolders = [], array $ignoredJobs = [], array $requiredChecks = []): JobsCollection
    {
        $localJobs = new JobsCollection();
        foreach ($this->finder->getWorkflows($possibleFolders) as $workflowFile) {
            $readCollection = $this->createFromYaml($workflowFile, $ignoredJobs);
            $localJobs->mergeCollection($readCollection);
        }

        // External / non-workflow required checks (e.g. codecov) enter the desired set as bare-name jobs,
        // so sync preserves them like any other required context instead of removing what it cannot read.
        foreach ($requiredChecks as $checkName) {
            $localJobs->addOrMergeJob(Job::fromContextName($checkName));
        }

        return $localJobs;
    }

    /**
     * @param array<array-key, string> $ignoredJobs job ids to skip while reading the workflow
     */
    public function createFromYaml(SplFileInfo $fileInfo, array $ignoredJobs = []): JobsCollection
    {
        $yaml = file_get_contents($fileInfo->getPathname());
        if (false === $yaml) {
            throw new \RuntimeException(sprintf('Unable to read the workflow file "%s".', $fileInfo->getPathname()));
        }

        $parsed = Yaml::parse($yaml);

        if (false === is_array($parsed)) {
            throw new \RuntimeException('The parsed YAML file is not an array.');
        }

        if (false === array_key_exists('name', $parsed)) {
            throw new \RuntimeException('The parsed YAML file does not contain a "name" key.');
        }

        if (false === array_key_exists('jobs', $parsed)) {
            throw new \RuntimeException('The parsed YAML file does not contain a "jobs" key.');
        }

        $workflowName = $parsed['name'];
        $jobs         = $parsed['jobs'];

        $localJobs = new JobsCollection();
        foreach ($jobs as $jobName => $jobContent) {
            if (in_array($jobName, $ignoredJobs, true)) {
                continue;
            }

            // Some contexts cannot be derived statically (interpolated job name, dynamic/fromJson matrix):
            // computing one would produce a wrong context that sync might wrongly remove/recreate. Warn and
            // skip, leaving the real context to be preserved via a declared required check or a gate job.
            if (is_array($jobContent)) {
                $nonDerivableReason = $this->nonDerivableContextDetector->detect($jobContent);
                if (null !== $nonDerivableReason) {
                    $localJobs->addWarning(sprintf(
                        'Job "%s" (workflow "%s"): %s. Its real check context cannot be computed; declare it with addRequiredCheck() (or use a static "gate" job) so sync preserves it instead of removing it.',
                        is_scalar($jobName) ? (string) $jobName : '',
                        is_scalar($workflowName) ? (string) $workflowName : '',
                        $nonDerivableReason,
                    ));

                    continue;
                }
            }

            $job = Job::createFromArray($jobName, $jobContent, $fileInfo->getFilename(), $workflowName, $jobName);
            $localJobs->addJob($job);
        }

        return $localJobs;
    }
}
