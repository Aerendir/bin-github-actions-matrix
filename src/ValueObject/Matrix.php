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

final class Matrix
{
    /**
     * @param array<string, Combination> $combinations
     */
    public function __construct(private array $combinations)
    {
        foreach ($this->combinations as $combinationAsString => $combination) {
            if (false === is_string($combinationAsString)) {
                throw new \InvalidArgumentException('The combination key must be a string.');
            }

            if (false === $combination instanceof Combination) {
                throw new \InvalidArgumentException('The combinations must be instances of Combination.');
            }
        }
    }

    /**
     * @param array<string, array<string>> $matrix
     */
    public static function createFromArray(array $matrix, string $workflowFilename, string $workflowName, string $job): self
    {
        $combinations = self::generateCombinations($matrix, $workflowFilename, $workflowName, $job);

        return new self($combinations);
    }

    /**
     * @return array<Combination>
     */
    public function getCombinations(): array
    {
        return $this->combinations;
    }

    public function merge(Matrix $matrix): void
    {
        $this->combinations = [...$this->combinations, ...$matrix->getCombinations()];
    }

    /**
     * @param array<string, array<string>> $matrix
     * @param array<string>                $currentCombination
     *
     * @return array<Combination>
     */
    private static function generateCombinations(array $matrix, string $workflowFilename, string $workflowName, string $job, array $currentCombination = []): array
    {
        if ([] === $matrix) {
            $combination = new Combination($currentCombination, $workflowFilename, $workflowName, $job);

            return [(string) $combination => $combination];
        }

        $combinations          = [];
        $remainingMatrixValues = $matrix;
        $matrixKey             = key($matrix);
        $matrixValues          = array_shift($remainingMatrixValues);

        if (false === is_array($matrixValues)) {
            throw new \InvalidArgumentException('The matrix must be an array of arrays.');
        }

        foreach ($matrixValues as $matrixValue) {
            $newCombination             = $currentCombination;
            $newCombination[$matrixKey] = $matrixValue;
            $combinations               = [...$combinations, ...self::generateCombinations($remainingMatrixValues, $workflowFilename, $workflowName, $job, $newCombination)];
        }

        return $combinations;
    }
}
