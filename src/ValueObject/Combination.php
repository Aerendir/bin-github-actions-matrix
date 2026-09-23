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

class Combination implements \Stringable
{
    final public const string ACTION_NOTHING = 'Nothing';
    final public const string ACTION_SYNC    = 'Sync';
    final public const string ACTION_REMOVE  = 'Remove';

    private bool $toSync     = false;
    private bool $toRemove   = false;
    private bool $isOptional = false;

    /**
     * @param array<string, string> $combination
     */
    public function __construct(private readonly array $combination, private readonly string $workflowFilename, private readonly string $workflowName, private readonly string $job)
    {
    }

    public function __toString(): string
    {
        // A non-matrix job has no combination values: its required-check context on GitHub is the bare
        // job name (e.g. "build"), without the "(...)" suffix used for matrix combinations.
        if ([] === $this->combination) {
            return $this->getJob();
        }

        return sprintf('%s (%s)', $this->getJob(), implode(', ', $this->getCombination()));
    }

    /**
     * @return array<string, string>
     */
    public function getCombination(): array
    {
        return $this->combination;
    }

    public function getWorkflowFilename(): string
    {
        return $this->workflowFilename;
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function getJob(): string
    {
        return $this->job;
    }

    public function setToSync(bool $toSync = true): void
    {
        $this->toSync = $toSync;
    }

    public function setToRemove(bool $toRemove = true): void
    {
        $this->toRemove = $toRemove;
    }

    public function isToRemove(): bool
    {
        return $this->toRemove;
    }

    public function isToSync(): bool
    {
        return $this->toSync;
    }

    public function setIsOptional(bool $isOptional = true): void
    {
        $this->isOptional = $isOptional;
    }

    public function isOptional(): bool
    {
        return $this->isOptional;
    }

    public function getAction(): string
    {
        // Removal wins over synchronisation: a combination that is both to sync and to remove is
        // removed. Keep this order, it is the behaviour asserted by the tests.
        if ($this->isToRemove()) {
            return self::ACTION_REMOVE;
        }

        if ($this->isToSync()) {
            return self::ACTION_SYNC;
        }

        return self::ACTION_NOTHING;
    }

    public function contains(self $combination): bool
    {
        if ([] === $this->combination) {
            return false;
        }

        $combination = $combination->getCombination();
        foreach ($this->combination as $key => $value) {
            if (false === array_key_exists($key, $combination)) {
                return false;
            }

            if ($combination[$key] !== $value) {
                return false;
            }
        }

        return true;
    }
}
