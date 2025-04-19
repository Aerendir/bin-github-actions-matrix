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

use Aerendir\Bin\GitHubActionsMatrix\ValueObject\JobsCollection;

class Comparator
{
    /**
     * @param array<string> $remoteJobsIds
     *
     * @return array<string>
     */
    public function compare(JobsCollection $localJobs, array $remoteJobsIds): array
    {
        $toSync = $this->getToSync($localJobs, $remoteJobsIds);

        foreach ($localJobs->getJobs() as $localJob) {
            foreach ($localJob->getMatrix()->getCombinations() as $combination) {
                if (in_array((string) $combination, $toSync)) {
                    $combination->setToSync();
                }
            }
        }

        return $this->getToRemove($localJobs, $remoteJobsIds);
    }

    /**
     * @param array<string> $remoteJobsIds
     *
     * @return array<string>
     */
    private function getToSync(JobsCollection $localJobs, array $remoteJobsIds): array
    {
        $localJobsIds = $localJobs->getJobsIds();

        return array_diff($localJobsIds, $remoteJobsIds);
    }

    /**
     * @param array<string> $remoteJobsIds
     *
     * @return array<string>
     */
    private function getToRemove(JobsCollection $localJobs, array $remoteJobsIds): array
    {
        $localJobsIds = $localJobs->getJobsIds();

        return array_values(array_diff($remoteJobsIds, $localJobsIds));
    }
}
