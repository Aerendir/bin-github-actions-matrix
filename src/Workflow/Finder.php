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
    /** @var string When installed in the main `composer.json` */
    private const string FROM_VENDOR = __DIR__ . '/../../.github/workflows';

    /** @var string When installed in `vendor-bin/[namespace]/vendor` by `bamarni/bin-composer-plugin´ */
    private const string FROM_VENDOR_BIN_VENDOR = __DIR__ . '/../../../../../../../.github/workflows';

    private readonly SymfonyFinder $finder;

    /**
     * @param array<array-key, string> $possibleFolders
     */
    public function __construct(array $possibleFolders = [self::FROM_VENDOR, self::FROM_VENDOR_BIN_VENDOR])
    {
        $finder = new SymfonyFinder();

        $foundFolder = null;
        foreach ($possibleFolders as $folder) {
            if (false === file_exists($folder)) {
                continue;
            }

            $foundFolder = $folder;

            break;
        }

        if (null === $foundFolder) {
            throw new \RuntimeException('Impossible to locate the GitHub workflows folder');
        }

        $this->finder = $finder->files()->name('*.yml')->in($foundFolder);
    }

    /**
     * @return \Iterator<string, SplFileInfo>
     */
    public function getWorkflows(): \Iterator
    {
        return $this->finder->getIterator();
    }
}
