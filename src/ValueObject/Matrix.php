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
     * @param array<string, mixed> $matrix
     */
    public static function createFromArray(array $matrix, string $workflowFilename, string $workflowName, string $job): self
    {
        $excludedCombinations = self::prepareExcludedCombinations($matrix['exclude'] ?? [], $workflowFilename, $workflowName, $job);
        unset($matrix['exclude']);

        // "include" is applied after "exclude": pull it out before generating the cartesian product so it is
        // not mistaken for a matrix dimension. The original dimension keys (what is left now) drive whether an
        // include entry may merge into a base combination or must become a new one.
        $includeEntries     = $matrix['include'] ?? [];
        unset($matrix['include']);
        $originalMatrixKeys = array_keys($matrix);

        $combinations = self::generateCombinations($matrix, $workflowFilename, $workflowName, $job);

        $filteredCombinations = self::filterOutExcludedCombinations($combinations, $excludedCombinations);

        if ([] === $includeEntries) {
            return new self($filteredCombinations);
        }

        $withIncludes = self::applyIncludes($filteredCombinations, $includeEntries, $originalMatrixKeys, $workflowFilename, $workflowName, $job);

        return new self($withIncludes);
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
     * @return array<string, Combination>
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

    /**
     * @param array<array-key, array<string, string>> $excludedCombinations
     *
     * @return array<string, Combination>
     */
    private static function prepareExcludedCombinations(array $excludedCombinations, string $workflowFilename, string $workflowName, string $job): array
    {
        $result = [];
        foreach ($excludedCombinations as $combination) {
            $combinationObject                   = new Combination($combination, $workflowFilename, $workflowName, $job);
            $result[(string) $combinationObject] = $combinationObject;
        }

        return $result;
    }

    /**
     * @param array<string, Combination> $combinations
     * @param array<string, Combination> $excludedCombinations
     *
     * @return array<string, Combination>
     */
    private static function filterOutExcludedCombinations(array $combinations, array $excludedCombinations): array
    {
        $filteredCombinations = [];
        foreach ($combinations as $id => $combination) {
            $shouldExclude = false;

            foreach ($excludedCombinations as $excludedCombination) {
                if (true === $excludedCombination->contains($combination)) {
                    $shouldExclude = true;

                    break;
                }
            }

            if (false === $shouldExclude) {
                $filteredCombinations[$id] = $combination;
            }
        }

        return $filteredCombinations;
    }

    /**
     * Implements GitHub's documented "include" algorithm, applied after "exclude" and processing entries in
     * order. Each entry merges into every base combination it does not conflict with — it may not overwrite an
     * ORIGINAL matrix value, though it may overwrite a value added by an earlier include, and a single entry
     * can touch many combinations. An entry that fits no base combination becomes a new combination; new
     * combinations are not themselves candidates for later include merges. When the matrix declares no real
     * dimensions (include-only), there is no base to merge into, so every entry is a standalone combination.
     *
     * @param array<string, Combination> $baseCombinations   the post-exclude combinations
     * @param array<array-key, mixed>    $includeEntries     raw "include" entries as read from the YAML
     * @param array<int, string>         $originalMatrixKeys
     *
     * @return array<string, Combination>
     */
    private static function applyIncludes(array $baseCombinations, array $includeEntries, array $originalMatrixKeys, string $workflowFilename, string $workflowName, string $job): array
    {
        // Work on plain value-maps so include entries can merge in declaration order; Combinations are rebuilt
        // at the end. The synthetic single empty combination produced for a dimension-less matrix is not a real
        // base, so it is dropped here when there are no original keys.
        $baseValues = [];
        if ([] !== $originalMatrixKeys) {
            foreach ($baseCombinations as $combination) {
                $baseValues[] = self::normalizeStringMap($combination->getCombination());
            }
        }

        $extraValues = [];
        foreach ($includeEntries as $includeEntry) {
            if (false === is_array($includeEntry)) {
                throw new \InvalidArgumentException('Each "include" entry must be an array.');
            }

            $includeEntry = self::normalizeStringMap($includeEntry);

            $merged = false;
            foreach ($baseValues as $index => $baseCombination) {
                if (false === self::includeFitsCombination($includeEntry, $baseCombination, $originalMatrixKeys)) {
                    continue;
                }

                // Original keys already match, so this only inserts/overwrites added keys while preserving the
                // existing key order (PHP keeps the position of an overwritten key, appends a new one).
                foreach ($includeEntry as $key => $value) {
                    $baseValues[$index][$key] = $value;
                }

                $merged = true;
            }

            if (false === $merged) {
                $extraValues[] = $includeEntry;
            }
        }

        $result = [];
        foreach ([...$baseValues, ...$extraValues] as $values) {
            $combination                   = new Combination($values, $workflowFilename, $workflowName, $job);
            $result[(string) $combination] = $combination;
        }

        return $result;
    }

    /**
     * An include entry fits a base combination when it does not overwrite any original matrix value: every key
     * it shares with the original dimensions must carry the same value. Added (non-dimension) keys never clash.
     *
     * @param array<string, string> $includeEntry
     * @param array<string, string> $baseCombination
     * @param array<int, string>    $originalMatrixKeys
     */
    private static function includeFitsCombination(array $includeEntry, array $baseCombination, array $originalMatrixKeys): bool
    {
        foreach ($includeEntry as $key => $value) {
            if (false === in_array($key, $originalMatrixKeys, true)) {
                continue;
            }

            if (false === array_key_exists($key, $baseCombination) || $baseCombination[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<array-key, mixed> $map
     *
     * @return array<string, string>
     */
    private static function normalizeStringMap(array $map): array
    {
        $normalized = [];
        foreach ($map as $key => $value) {
            if (false === is_scalar($value)) {
                throw new \InvalidArgumentException('The "include" values must be scalar.');
            }

            $normalized[(string) $key] = (string) $value;
        }

        return $normalized;
    }
}
