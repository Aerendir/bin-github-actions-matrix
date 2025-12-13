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

namespace Aerendir\Bin\GitHubActionsMatrix\Console;

use Aerendir\Bin\GitHubActionsMatrix\Console\Command\CompareCommand;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\SyncCommand;
use Symfony\Component\Console\Application as BaseApplication;

final class Application extends BaseApplication
{
    public const string NAME    = 'Github Actions Matrix';
    public const string VERSION = '1.0.0';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->addCommand(new CompareCommand());
        $this->addCommand(new SyncCommand());
    }
}
