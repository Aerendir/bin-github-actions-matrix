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

use function Safe\getcwd;
use function Safe\realpath;

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

        // Try to load gh-actions-matrix.php first, then fallback to gh-actions-matrix.dist.php
        $configFiles = [
            'gh-actions-matrix.php',
            'gh-actions-matrix.dist.php',
        ];

        foreach ($configFiles as $configFileName) {
            $configFile = $currentDir . '/' . $configFileName;

            // Security check: ensure the file exists and is within the current directory
            if (file_exists($configFile)) {
                $realConfigPath = realpath($configFile);
                $realCurrentDir = realpath($currentDir);

                // Validate config is within the current directory
                $expectedPath = $realCurrentDir . DIRECTORY_SEPARATOR . $configFileName;
                if ($realConfigPath === $expectedPath) {
                    $config = require $realConfigPath;

                    if ( ! $config instanceof GHMatrixConfig) {
                        throw new \RuntimeException(sprintf('The config file "%s" must return an instance of %s, got %s', $configFile, GHMatrixConfig::class, get_debug_type($config)));
                    }

                    return $config;
                }
            }
        }

        return new GHMatrixConfig();
    }
}
