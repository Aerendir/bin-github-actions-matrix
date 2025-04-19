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

namespace Aerendir\Bin\GitHubActionsMatrix\Repo;

use Aerendir\Bin\GitHubActionsMatrix\Utils\Shell;
use Safe\Exceptions\ExecException;

use function Safe\preg_match;

readonly class Reader
{
    public function __construct(private Shell $shell = new Shell())
    {
    }

    public function getUsername(): ?string
    {
        try {
            $result = $this->shell->exec('git config user.name');
        } catch (ExecException) {
            return null;
        }

        return trim($result);
    }

    public function getRepoName(): string
    {
        $repoUrl  = trim($this->shell->exec('git remote get-url origin'));
        if (
            0       === preg_match('/\/([^\/]+)\.git$/', $repoUrl, $matches)
            || null === $matches
        ) {
            throw new \RuntimeException('Cannot find the name of the repo.');
        }

        // Get the URL of the repo from its URL
        $repoName = $matches[1];

        if (null === $repoName) {
            throw new \RuntimeException('Cannot find the name of the repo.');
        }

        return $repoName;
    }
}
