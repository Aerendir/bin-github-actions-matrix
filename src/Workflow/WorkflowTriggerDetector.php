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
 * Reads a workflow's `on:` triggers and tells whether the workflow can ever run on a pull request.
 *
 * Only workflows that run on `push`, `pull_request` or `pull_request_target` can report a status check
 * on a pull request; a workflow triggered solely by, say, `schedule` or `workflow_dispatch` never does,
 * so its jobs must not enter the required-checks set (they would sit forever as "expected" checks).
 */
final class WorkflowTriggerDetector
{
    /** Triggers under which a workflow can run on — and therefore report a check for — a pull request. */
    private const array PULL_REQUEST_TRIGGERS = ['push', 'pull_request', 'pull_request_target'];

    /**
     * The normalized (trimmed, lower-cased, de-duplicated) list of events declared under the workflow's
     * `on:` key, across the three shapes GitHub accepts:
     *   - string: `on: push`
     *   - list:   `on: [push, pull_request]`
     *   - map:    `on: { push: ..., schedule: ... }` (the event names are the keys).
     *
     * @param array<array-key, mixed> $parsedWorkflow the whole parsed workflow YAML
     *
     * @return list<string>
     */
    public function detectTriggers(array $parsedWorkflow): array
    {
        $on = $parsedWorkflow['on'] ?? null;

        $events = match (true) {
            is_string($on) => [$on],
            is_array($on)  => array_is_list($on) ? $on : array_keys($on),
            default        => [],
        };

        $normalized = [];
        foreach ($events as $event) {
            if (false === is_scalar($event)) {
                continue;
            }

            $name = trim(strtolower((string) $event));
            if ('' !== $name) {
                $normalized[] = $name;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Whether at least one declared trigger can run the workflow on a pull request.
     *
     * @param list<string> $triggers as returned by {@see self::detectTriggers()}
     */
    public function isPullRequestEligible(array $triggers): bool
    {
        return [] !== array_intersect(self::PULL_REQUEST_TRIGGERS, $triggers);
    }
}
