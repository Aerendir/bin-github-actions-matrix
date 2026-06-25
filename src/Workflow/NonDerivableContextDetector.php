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

/**
 * Detects jobs whose required-check context name(s) cannot be derived statically from the workflow YAML.
 *
 * Two cases make a context non-derivable:
 *   1. an interpolated job `name:` (e.g. `name: build ${{ matrix.os }}`): GitHub shows the resolved value,
 *      not the literal `job (values)` the tool would compute;
 *   2. a dynamic matrix (`matrix: ${{ fromJson(...) }}`, or matrix keys/values built from expressions): the
 *      values do not exist as literal arrays in the file, so the combinations cannot be computed at all.
 *
 * In both cases the tool must NOT compute a (wrong) context; the caller warns the user and relies on the
 * "required checks" preservation so the real context name can be declared instead of being miscomputed.
 */
final class NonDerivableContextDetector
{
    private const string EXPRESSION_MARKER = '${{';
    private const string FROM_JSON_MARKER  = 'fromJson';

    /**
     * Returns a human-readable reason when the job's context(s) cannot be derived statically, or null when
     * they are fully derivable.
     *
     * @param array<array-key, mixed> $jobContent the raw job definition as read from the YAML
     */
    public function detect(array $jobContent): ?string
    {
        if (array_key_exists('name', $jobContent) && $this->containsExpression($jobContent['name'])) {
            return 'its "name" is built from an expression, so the real check context uses the interpolated value';
        }

        $strategy = $jobContent['strategy'] ?? null;
        if (false === is_array($strategy) || false === array_key_exists('matrix', $strategy)) {
            return null;
        }

        $matrix = $strategy['matrix'];

        // `matrix: ${{ fromJson(...) }}` — the whole matrix is an expression: there are no literal values.
        if (false === is_array($matrix)) {
            return $this->containsExpression($matrix)
                ? 'its matrix is generated dynamically from an expression (no literal values to expand)'
                : null;
        }

        if ($this->containsExpression($matrix)) {
            return 'its matrix uses expression-based keys or values (no literal values to expand)';
        }

        return null;
    }

    /**
     * True when the value (or, recursively, any of its keys/leaves) carries a GitHub expression or a
     * `fromJson` call — the markers that make a context non-derivable.
     */
    private function containsExpression(mixed $value): bool
    {
        if (is_string($value)) {
            return str_contains($value, self::EXPRESSION_MARKER) || str_contains($value, self::FROM_JSON_MARKER);
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if (is_string($key) && (str_contains($key, self::EXPRESSION_MARKER) || str_contains($key, self::FROM_JSON_MARKER))) {
                    return true;
                }

                if ($this->containsExpression($item)) {
                    return true;
                }
            }
        }

        return false;
    }
}
