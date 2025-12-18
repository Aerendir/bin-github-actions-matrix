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

use function Safe\json_encode;

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
        $this->applyOptionalCombinations($localJobs);
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
     * Apply optional combination markings to the jobs collection.
     */
    private function applyOptionalCombinations(JobsCollection $localJobs): void
    {
        if (null === $this->config) {
            return;
        }

        foreach ($localJobs->getJobs() as $localJob) {
            $workflowName           = $localJob->getName();
            $optionalCombinations   = $this->config->getOptionalCombinations($workflowName);

            if ([] === $optionalCombinations) {
                continue;
            }

            foreach ($optionalCombinations as $optionalCombination) {
                $this->validateAndMarkOptionalCombination($localJob->getMatrix()->getCombinations(), $optionalCombination, $workflowName);
            }
        }
    }

    /**
     * Validate that an optional combination exists in the matrix and mark it as optional.
     *
     * @param array<string, Combination> $combinations
     * @param array<string, string>      $optionalCombination
     */
    private function validateAndMarkOptionalCombination(array $combinations, array $optionalCombination, string $workflowName): void
    {
        $found = false;

        foreach ($combinations as $combination) {
            if ($this->combinationMatches($combination->getCombination(), $optionalCombination)) {
                $combination->setIsOptional();
                $found = true;
            }
        }

        if (false === $found) {
            throw new \InvalidArgumentException(sprintf('The optional combination %s for workflow "%s" does not exist in the matrix or is explicitly excluded.', json_encode($optionalCombination), $workflowName));
        }
    }

    /**
     * Check if a combination matches the optional combination criteria.
     *
     * @param array<string, string> $combination
     * @param array<string, string> $optionalCombination
     */
    private function combinationMatches(array $combination, array $optionalCombination): bool
    {
        foreach ($optionalCombination as $key => $value) {
            if ( ! array_key_exists($key, $combination) || $combination[$key] !== $value) {
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
