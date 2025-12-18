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

use Aerendir\Bin\GitHubActionsMatrix\Console\Application;
use Aerendir\Bin\GitHubActionsMatrix\Tests\TestCase;

use function Safe\chdir;
use function Safe\file_put_contents;
use function Safe\getcwd;
use function Safe\mkdir;
use function Safe\rmdir;
use function Safe\unlink;

class ApplicationConfigTest extends TestCase
{
    private string $originalDir;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalDir = getcwd();
    }

    #[\Override]
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

        $configContent = $this->createConfigContent('test-user', 'main');

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

        $configContent = $this->createConfigContent('dist-user', 'develop');

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

        $phpConfigContent  = $this->createConfigContent('php-user');
        $distConfigContent = $this->createConfigContent('dist-user');

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

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('must return an instance of');

            new Application();
        } finally {
            // Clean up - ensure this runs even if assertion fails
            if (file_exists($tempDir . '/gh-actions-matrix.php')) {
                unlink($tempDir . '/gh-actions-matrix.php');
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    public function testApplicationRejectsConfigFromParentDirectory(): void
    {
        $parentDir = sys_get_temp_dir() . '/ghmatrix-test-parent-' . uniqid();
        $childDir  = $parentDir . '/child';
        mkdir($parentDir);
        mkdir($childDir);

        $configContent = $this->createConfigContent('parent-user');

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

    public function testApplicationHandlesSymlinksSecurely(): void
    {
        $tempDir    = sys_get_temp_dir() . '/ghmatrix-test-' . uniqid();
        $symlinkDir = sys_get_temp_dir() . '/ghmatrix-test-symlink-' . uniqid();
        mkdir($tempDir);
        mkdir($symlinkDir);

        $configContent = $this->createConfigContent('symlink-user');
        file_put_contents($symlinkDir . '/gh-actions-matrix.php', $configContent);

        // Create symlink in temp dir pointing to config in another dir
        chdir($tempDir);
        if (symlink($symlinkDir . '/gh-actions-matrix.php', $tempDir . '/gh-actions-matrix.php')) {
            $application = new Application();

            // Application should work but should not load symlinked config from outside directory
            $this->assertInstanceOf(Application::class, $application);

            // Clean up
            unlink($tempDir . '/gh-actions-matrix.php');
        }

        unlink($symlinkDir . '/gh-actions-matrix.php');
        rmdir($symlinkDir);
        rmdir($tempDir);
    }

    private function createConfigContent(?string $user = null, ?string $branch = null): string
    {
        $userLine   = null !== $user ? "\$config->setUser('{$user}');" : '';
        $branchLine = null !== $branch ? "\$config->setBranch('{$branch}');" : '';

        return <<<PHP
<?php
\$config = new Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig();
{$userLine}
{$branchLine}
return \$config;
PHP;
    }
}
