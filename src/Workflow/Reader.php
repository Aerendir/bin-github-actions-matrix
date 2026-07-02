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
    public function __construct(private readonly Finder $finder = new Finder(), private readonly NonDerivableContextDetector $nonDerivableContextDetector = new NonDerivableContextDetector(), private readonly WorkflowTriggerDetector $workflowTriggerDetector = new WorkflowTriggerDetector())
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

        // A workflow that never runs on a pull request can never report a check there: keeping its jobs
        // would leave permanently-pending "expected" checks that block PRs. Skip the whole workflow and
        // record it as info (expected, no action needed), not as a warning.
        $triggers = $this->workflowTriggerDetector->detectTriggers($parsed);
        if (false === $this->workflowTriggerDetector->isPullRequestEligible($triggers)) {
            $localJobs->addNotice(sprintf(
                'Workflow "%s" is not triggered by push/pull_request/pull_request_target (triggers: %s); its jobs never run on pull requests, so they are excluded from the required-checks set.',
                self::asString($workflowName),
                [] === $triggers ? 'none' : implode(', ', $triggers),
            ));

            return $localJobs;
        }

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
                        self::asString($jobName),
                        self::asString($workflowName),
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

    /**
     * Renders a value read from the YAML as a string for user-facing messages; non-scalars (arrays,
     * null) collapse to an empty string, since they only appear in malformed workflows.
     */
    private static function asString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
