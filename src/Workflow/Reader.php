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

    public function read(): JobsCollection
    {
        $localJobs = new JobsCollection();
        foreach ($this->finder->getWorkflows() as $workflowFile) {
            $readCollection = $this->createFromYaml($workflowFile);
            $localJobs->mergeCollection($readCollection);
        }

        return $localJobs;
    }

    public function createFromYaml(SplFileInfo $fileInfo): JobsCollection
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

        foreach ($jobs as $jobName => $jobContent) {
            $jobs[$jobName] = Job::createFromArray($jobName, $jobContent, $fileInfo->getFilename(), $workflowName, $jobName);
        }

        return new JobsCollection($jobs);
    }
}
