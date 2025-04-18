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

use Symfony\Component\Finder\Finder as SymfonyFinder;
use Symfony\Component\Finder\SplFileInfo;

class Finder
{
    private readonly SymfonyFinder $finder;

    public function __construct()
    {
        $finder = new SymfonyFinder();
        $folder = __DIR__ . '/../../.github/workflows';

        if (false === file_exists($folder)) {
            throw new \RuntimeException('The folder ' . $folder . ' does not exist.');
        }

        $this->finder = $finder->files()->name('*.yml')->in($folder);
    }

    /**
     * @return \Iterator<string, SplFileInfo>
     */
    public function getWorkflows(): \Iterator
    {
        return $this->finder->getIterator();
    }
}
