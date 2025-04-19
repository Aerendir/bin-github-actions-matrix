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

use function Safe\shell_exec;

#[CodeCoverageIgnore]
class Shell
{
    public function exec(string $command): string
    {
        return shell_exec($command);
    }
}
