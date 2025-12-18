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

use Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig;
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

        $config = $this->loadConfig();

        $this->addCommand(new CompareCommand(config: $config));
        $this->addCommand(new SyncCommand(config: $config));
    }

    private function loadConfig(): GHMatrixConfig
    {
        $currentDir = getcwd();
        if (false === $currentDir) {
            return new GHMatrixConfig();
        }

        $configFile = $currentDir . '/gh-actions-matrix.php';

        // Security check: ensure the file path is within the current directory
        $realConfigPath = realpath($configFile);
        if (false !== $realConfigPath && str_starts_with($realConfigPath, $currentDir) && file_exists($realConfigPath)) {
            $config = require $realConfigPath;

            if (!$config instanceof GHMatrixConfig) {
                throw new \RuntimeException('The config file must return an instance of ' . GHMatrixConfig::class);
            }

            return $config;
        }

        return new GHMatrixConfig();
    }
}
