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

namespace Aerendir\Bin\GitHubActionsMatrix\ValueObject;

class JobsCollection
{
    /** @var array<string> $ids */
    private array $ids = [];

    /**
     * @param array<string, Job> $jobs
     */
    public function __construct(private array $jobs = [])
    {
    }

    public function mergeCollection(JobsCollection $collection): void
    {
        foreach ($collection->getJobs() as $job) {
            $this->addOrMergeJob($job);
        }
    }

    public function addOrMergeJob(Job $job): void
    {
        $this->hasJob($job->getName())
            ? $this->getJob($job->getName())->getMatrix()->merge($job->getMatrix())
            : $this->addJob($job);
    }

    public function addJob(Job $job): void
    {
        if ($this->hasJob($job->getName())) {
            throw new \RuntimeException(sprintf('The job "%s" already exists in the collection.', $job->getName()));
        }

        $this->jobs[$job->getName()] = $job;
    }

    public function getJob(string $name): Job
    {
        return $this->jobs[$name];
    }

    public function hasJob(string $name): bool
    {
        return array_key_exists($name, $this->jobs);
    }

    /**
     * @return array<Job>
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }

    /**
     * @return array<string>
     */
    public function getJobsIds(): array
    {
        if ([] === $this->ids) {
            $ids = [];
            foreach ($this->getJobs() as $job) {
                foreach ($job->getMatrix()->getCombinations() as $combination) {
                    $ids[] = (string) $combination;
                }
            }

            $this->ids = $ids;
        }

        return $this->ids;
    }
}
