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

use function Safe\file_get_contents;

class Reader
{
    public function __construct(private readonly Finder $finder = new Finder())
    {
    }

    /**
     * @param array<array-key, string> $possibleFolders explicit folders to look into, in priority order;
     *                                                  the Finder appends the package fallbacks after them
     * @param array<array-key, string> $ignoredJobs     job ids to exclude from the computed set entirely
     */
    public function read(array $possibleFolders = [], array $ignoredJobs = []): JobsCollection
    {
        $localJobs = new JobsCollection();
        foreach ($this->finder->getWorkflows($possibleFolders) as $workflowFile) {
            $readCollection = $this->createFromYaml($workflowFile, $ignoredJobs);
            $localJobs->mergeCollection($readCollection);
        }

        return $localJobs;
    }

    /**
     * @param array<array-key, string> $ignoredJobs job ids to skip while reading the workflow
     */
    public function createFromYaml(SplFileInfo $fileInfo, array $ignoredJobs = []): JobsCollection
    {
        $parsed = Yaml::parse(file_get_contents($fileInfo->getPathname()));

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

            $job = Job::createFromArray($jobName, $jobContent, $fileInfo->getFilename(), $workflowName, $jobName);
            $localJobs->addJob($job);
        }

        return $localJobs;
    }
}
