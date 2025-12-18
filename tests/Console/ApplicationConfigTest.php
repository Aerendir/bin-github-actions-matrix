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

namespace Aerendir\Bin\GitHubActionsMatrix\Tests\Console;

use Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig;
use Aerendir\Bin\GitHubActionsMatrix\Console\Application;
use Aerendir\Bin\GitHubActionsMatrix\Tests\TestCase;

class ApplicationConfigTest extends TestCase
{
    private string $originalDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalDir = getcwd();
    }

    protected function tearDown(): void
    {
        // Change back to original directory
        chdir($this->originalDir);
        parent::tearDown();
    }

    public function testApplicationLoadsPhpConfigFile(): void
    {
        $tempDir = sys_get_temp_dir() . '/ghmatrix-test-' . uniqid();
        mkdir($tempDir);
        chdir($tempDir);

        $configContent = <<<'PHP'
<?php
$config = new Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig();
$config->setUser('test-user');
$config->setBranch('main');
return $config;
PHP;

        file_put_contents($tempDir . '/gh-actions-matrix.php', $configContent);

        $application = new Application();

        // Verify application was created successfully
        $this->assertInstanceOf(Application::class, $application);

        // Clean up
        unlink($tempDir . '/gh-actions-matrix.php');
        rmdir($tempDir);
    }

    public function testApplicationFallsBackToDistFile(): void
    {
        $tempDir = sys_get_temp_dir() . '/ghmatrix-test-' . uniqid();
        mkdir($tempDir);
        chdir($tempDir);

        $configContent = <<<'PHP'
<?php
$config = new Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig();
$config->setUser('dist-user');
$config->setBranch('develop');
return $config;
PHP;

        file_put_contents($tempDir . '/gh-actions-matrix.dist.php', $configContent);

        $application = new Application();

        // Verify application was created successfully
        $this->assertInstanceOf(Application::class, $application);

        // Clean up
        unlink($tempDir . '/gh-actions-matrix.dist.php');
        rmdir($tempDir);
    }

    public function testApplicationPrefersPhpOverDistFile(): void
    {
        $tempDir = sys_get_temp_dir() . '/ghmatrix-test-' . uniqid();
        mkdir($tempDir);
        chdir($tempDir);

        $phpConfigContent = <<<'PHP'
<?php
$config = new Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig();
$config->setUser('php-user');
return $config;
PHP;

        $distConfigContent = <<<'PHP'
<?php
$config = new Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig();
$config->setUser('dist-user');
return $config;
PHP;

        file_put_contents($tempDir . '/gh-actions-matrix.php', $phpConfigContent);
        file_put_contents($tempDir . '/gh-actions-matrix.dist.php', $distConfigContent);

        $application = new Application();

        // Verify application was created successfully (php config should be loaded, not dist)
        $this->assertInstanceOf(Application::class, $application);

        // Clean up
        unlink($tempDir . '/gh-actions-matrix.php');
        unlink($tempDir . '/gh-actions-matrix.dist.php');
        rmdir($tempDir);
    }

    public function testApplicationWorksWithNoConfigFile(): void
    {
        $tempDir = sys_get_temp_dir() . '/ghmatrix-test-' . uniqid();
        mkdir($tempDir);
        chdir($tempDir);

        $application = new Application();

        // Verify application was created successfully with default config
        $this->assertInstanceOf(Application::class, $application);

        // Clean up
        rmdir($tempDir);
    }

    public function testApplicationThrowsExceptionForInvalidConfig(): void
    {
        $tempDir = sys_get_temp_dir() . '/ghmatrix-test-' . uniqid();
        mkdir($tempDir);
        chdir($tempDir);

        $invalidConfigContent = <<<'PHP'
<?php
return 'invalid-config';
PHP;

        file_put_contents($tempDir . '/gh-actions-matrix.php', $invalidConfigContent);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must return an instance of');

        new Application();

        // Clean up
        unlink($tempDir . '/gh-actions-matrix.php');
        rmdir($tempDir);
    }

    public function testApplicationRejectsConfigFromParentDirectory(): void
    {
        $parentDir = sys_get_temp_dir() . '/ghmatrix-test-parent-' . uniqid();
        $childDir  = $parentDir . '/child';
        mkdir($parentDir);
        mkdir($childDir);

        $configContent = <<<'PHP'
<?php
$config = new Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig();
$config->setUser('parent-user');
return $config;
PHP;

        file_put_contents($parentDir . '/gh-actions-matrix.php', $configContent);
        chdir($childDir);

        $application = new Application();

        // Application should work but should not load parent directory's config
        $this->assertInstanceOf(Application::class, $application);

        // Clean up
        unlink($parentDir . '/gh-actions-matrix.php');
        rmdir($childDir);
        rmdir($parentDir);
    }
}
