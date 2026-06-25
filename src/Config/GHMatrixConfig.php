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
    private ?string $user         = null;
    private ?string $branch       = null;
    private ?string $repoName     = null;
    private ?string $tokenFile    = null;
    private ?string $projectDir   = null;
    private ?string $workflowsDir = null;

    /** @var array<string, array<array<string, string>>> */
    private array $optionalCombinations = [];

    /** @var array<string> */
    private array $ignoredJobs = [];

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

    public function getRepoName(): ?string
    {
        return $this->repoName;
    }

    public function setRepoName(?string $repoName): void
    {
        $this->repoName = $repoName;
    }

    public function getTokenFile(): ?string
    {
        return $this->tokenFile;
    }

    public function setTokenFile(?string $tokenFile): void
    {
        $this->tokenFile = $tokenFile;
    }

    public function getProjectDir(): ?string
    {
        return $this->projectDir;
    }

    /**
     * The project root that contains the `.github/workflows` folder.
     *
     * Used both to locate the workflows and as the preferred base directory to resolve the token file.
     */
    public function setProjectDir(?string $projectDir): void
    {
        $this->projectDir = $projectDir;
    }

    public function getWorkflowsDir(): ?string
    {
        return $this->workflowsDir;
    }

    /**
     * The folder that directly contains the workflow `*.yml`/`*.yaml` files.
     *
     * Escape hatch for non-standard layouts where the workflows do not live under `<projectDir>/.github/workflows`.
     */
    public function setWorkflowsDir(?string $workflowsDir): void
    {
        $this->workflowsDir = $workflowsDir;
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

        if ( ! isset($this->optionalCombinations[$workflowName])) {
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

    /**
     * Mark a job (by its job id, as written in the workflow file) as one that must NOT gate pull requests.
     *
     * Ignored jobs are excluded from the computed set entirely: they are never added to the branch
     * protection and, if currently present, they are removed by `sync`. Useful for jobs that should run
     * but never block merging (e.g. a deploy job).
     */
    public function ignoreJob(string $jobName): void
    {
        if ('' === $jobName) {
            throw new \InvalidArgumentException('The job name cannot be empty.');
        }

        $this->ignoredJobs[] = $jobName;
    }

    /**
     * @return array<string>
     */
    public function getIgnoredJobs(): array
    {
        return $this->ignoredJobs;
    }
}
