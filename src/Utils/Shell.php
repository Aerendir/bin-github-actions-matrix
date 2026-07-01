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

namespace Aerendir\Bin\GitHubActionsMatrix\Utils;

use PHPUnit\Framework\Attributes\CodeCoverageIgnore;

#[CodeCoverageIgnore]
class Shell
{
    public function exec(string $command): string
    {
        $output   = [];
        $exitCode = 0;
        $result   = exec($command . ' 2>&1', $output, $exitCode);
        if (false === $result) {
            throw new \RuntimeException(sprintf('Unable to execute the shell command "%s".', $command));
        }

        if (0 !== $exitCode) {
            throw new \RuntimeException(sprintf(
                'The shell command "%s" failed with exit code %d.%s%s',
                $command,
                $exitCode,
                [] !== $output ? ' Output:' : '',
                [] !== $output ? ' ' . implode(PHP_EOL, $output) : ''
            ));
        }

        return implode(PHP_EOL, $output);
    }
}
