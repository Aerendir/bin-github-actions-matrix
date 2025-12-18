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

namespace Aerendir\Bin\GitHubActionsMatrix\Config;

final class GHMatrixConfig
{
    private ?string $user      = null;
    private ?string $branch    = null;
    private ?string $tokenFile = null;

    /**
     * @var array<string, array<array<string, string>>>
     */
    private array $optionalCombinations = [];

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(?string $user): void
    {
        $this->user = $user;
    }

    public function getBranch(): ?string
    {
        return $this->branch;
    }

    public function setBranch(?string $branch): void
    {
        $this->branch = $branch;
    }

    public function getTokenFile(): ?string
    {
        return $this->tokenFile;
    }

    public function setTokenFile(?string $tokenFile): void
    {
        $this->tokenFile = $tokenFile;
    }

    /**
     * Mark a combination as "optional" (not required in branch protection rules).
     *
     * @param string                $workflowName The name of the workflow
     * @param array<string, string> $combination  The combination to mark as optional (e.g., ['php' => '8.4', 'symfony' => '~7.4'])
     */
    public function markOptionalCombination(string $workflowName, array $combination): void
    {
        if ('' === $workflowName) {
            throw new \InvalidArgumentException('The workflow name cannot be empty.');
        }

        if ([] === $combination) {
            throw new \InvalidArgumentException('The combination cannot be empty.');
        }

        if (!isset($this->optionalCombinations[$workflowName])) {
            $this->optionalCombinations[$workflowName] = [];
        }

        $this->optionalCombinations[$workflowName][] = $combination;
    }

    /**
     * Get all optional combinations for a specific workflow.
     *
     * @param string $workflowName The name of the workflow
     *
     * @return array<array<string, string>>
     */
    public function getOptionalCombinations(string $workflowName): array
    {
        return $this->optionalCombinations[$workflowName] ?? [];
    }

    /**
     * Get all optional combinations.
     *
     * @return array<string, array<array<string, string>>>
     */
    public function getAllOptionalCombinations(): array
    {
        return $this->optionalCombinations;
    }
}
