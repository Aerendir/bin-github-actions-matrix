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

namespace Aerendir\Bin\GitHubActionsMatrix\Tests;

use Symfony\Component\Finder\SplFileInfo;

use function Safe\file_put_contents;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function createTempFile(string $content, string $fileName = 'workflow'): SplFileInfo
    {
        $filePathname = sys_get_temp_dir() . sprintf('/%s.yaml', $fileName);

        file_put_contents($filePathname, $content);

        return new SplFileInfo($filePathname, dirname($filePathname), basename($filePathname));
    }
}
