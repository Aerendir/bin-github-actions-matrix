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
    // These are the package's own workflow locations, used as the LAST-resort fallbacks when no
    // explicit folder is given (or none of the explicit ones exist). They are an internal detail of
    // how the package is laid out on disk, so they are private: the CLI layer passes its candidate
    // folders to getWorkflows() and never needs to reference these directly.

    /** @var string When installed in the main `composer.json` */
    private const string FROM_VENDOR = __DIR__ . '/../../.github/workflows';

    /** @var string When installed in `vendor-bin/[namespace]/vendor` by `bamarni/bin-composer-plugin´ */
    private const string FROM_VENDOR_BIN_VENDOR = __DIR__ . '/../../../../../../../.github/workflows';

    /**
     * @param array<array-key, string> $fallbackFolders The package-relative fallbacks, always tried
     *                                                  LAST. Injectable so tests can exercise the
     *                                                  "no folder found" path deterministically.
     */
    public function __construct(
        private readonly array $fallbackFolders = [self::FROM_VENDOR, self::FROM_VENDOR_BIN_VENDOR],
    ) {
    }

    /**
     * @param array<array-key, string> $possibleFolders explicit candidate folders, tried first and in
     *                                                  order; the package fallbacks are appended after
     *
     * @return \Iterator<string, SplFileInfo>
     */
    public function getWorkflows(array $possibleFolders = []): \Iterator
    {
        $candidates = [...array_values($possibleFolders), ...array_values($this->fallbackFolders)];

        $foundFolder = null;
        foreach ($candidates as $folder) {
            if (false === file_exists($folder)) {
                continue;
            }

            $foundFolder = $folder;

            break;
        }

        if (null === $foundFolder) {
            throw new \RuntimeException('Impossible to locate the GitHub workflows folder');
        }

        return (new SymfonyFinder())->files()->name(['*.yml', '*.yaml'])->in($foundFolder)->getIterator();
    }
}
