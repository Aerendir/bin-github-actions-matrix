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

use Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig;
use Aerendir\Bin\GitHubActionsMatrix\ValueObject\Combination;
use Aerendir\Bin\GitHubActionsMatrix\ValueObject\JobsCollection;

class Comparator
{
    public function __construct(private readonly ?GHMatrixConfig $config = null)
    {
    }

    /**
     * @param array<string> $remoteJobsIds
     *
     * @return array<string>
     */
    public function compare(JobsCollection $localJobs, array $remoteJobsIds): array
    {
        $this->applySoftCombinations($localJobs);
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
     * Apply soft combination markings to the jobs collection.
     */
    private function applySoftCombinations(JobsCollection $localJobs): void
    {
        if (null === $this->config) {
            return;
        }

        $allSoftCombinations = $this->config->getAllSoftCombinations();
        if ([] === $allSoftCombinations) {
            return;
        }

        foreach ($localJobs->getJobs() as $localJob) {
            $workflowName       = $localJob->getName();
            $softCombinations   = $this->config->getSoftCombinations($workflowName);

            if ([] === $softCombinations) {
                continue;
            }

            foreach ($softCombinations as $softCombination) {
                $this->validateAndMarkSoftCombination($localJob->getMatrix()->getCombinations(), $softCombination, $workflowName);
            }
        }
    }

    /**
     * Validate that a soft combination exists in the matrix and mark it as soft.
     *
     * @param array<string, Combination> $combinations
     * @param array<string, string>      $softCombination
     */
    private function validateAndMarkSoftCombination(array $combinations, array $softCombination, string $workflowName): void
    {
        $found = false;

        foreach ($combinations as $combination) {
            if ($this->combinationMatches($combination->getCombination(), $softCombination)) {
                $combination->setIsSoft();
                $found = true;
            }
        }

        if (false === $found) {
            throw new \InvalidArgumentException(sprintf(
                'The soft combination %s for workflow "%s" does not exist in the matrix or is explicitly excluded.',
                json_encode($softCombination),
                $workflowName
            ));
        }
    }

    /**
     * Check if a combination matches the soft combination criteria.
     *
     * @param array<string, string> $combination
     * @param array<string, string> $softCombination
     */
    private function combinationMatches(array $combination, array $softCombination): bool
    {
        foreach ($softCombination as $key => $value) {
            if (!array_key_exists($key, $combination) || $combination[$key] !== $value) {
                return false;
            }
        }

        return true;
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
