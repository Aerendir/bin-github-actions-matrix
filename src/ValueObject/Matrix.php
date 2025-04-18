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

        $combinations    = [];
        $remainingArrays = $matrix;
        $currentArrayKey = key($matrix);
        $currentArray    = array_shift($remainingArrays);

        foreach ($currentArray as $element) {
            $newCombination                   = $currentCombination;
            $newCombination[$currentArrayKey] = $element;
            $combinations                     = [...$combinations, ...self::generateCombinations($remainingArrays, $workflowFilename, $workflowName, $job, $newCombination)];
        }

        return $combinations;
    }
}
