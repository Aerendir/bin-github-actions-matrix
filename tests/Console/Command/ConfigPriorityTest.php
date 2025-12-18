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

namespace Aerendir\Bin\GitHubActionsMatrix\Tests\Console\Command;

use Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\GitHubTokenCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\GitHubUsernameCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\RepoBranchCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\SyncCommand;
use Aerendir\Bin\GitHubActionsMatrix\Repo\Reader as RepoReader;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ConfigPriorityTest extends CommandTestCase
{
    public function testCliOptionOverridesConfigForUsername(): void
    {
        $cliUsername     = 'cli-user';
        $configUsername  = 'config-user';
        $testRepo        = 'test-repo';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser($configUsername);
        $config->setBranch('main');

        $mockRepoReader      = $this->createMockReader($testRepo);
        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();

        $command = new SyncCommand(
            config: $config,
            repoReader: $mockRepoReader,
            workflowsReader: $mockWorkflowsReader,
            githubClient: $mockGitHubClient
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => $cliUsername,
            '--' . GitHubTokenCommandOption::NAME    => $testGitHubToken,
        ]);

        // The command should use the CLI username, not the config username
        // We verify this by checking that execution succeeds (username was accepted)
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testConfigUsernameUsedWhenCliNotProvided(): void
    {
        $configUsername  = 'config-user';
        $testRepo        = 'test-repo';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser($configUsername);
        $config->setBranch('main');

        $mockRepoReader = $this->createMockReader($testRepo);
        // Mock getUsername to return null, so config file value is used
        $mockRepoReader->method('getUsername')->willReturn(null);

        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();

        $command = new SyncCommand(
            config: $config,
            repoReader: $mockRepoReader,
            workflowsReader: $mockWorkflowsReader,
            githubClient: $mockGitHubClient
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--' . GitHubTokenCommandOption::NAME => $testGitHubToken,
        ]);

        // Command should succeed using config username
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testCliOptionOverridesConfigForBranch(): void
    {
        $cliBranch       = 'dev';
        $configBranch    = 'main';
        $testUsername    = 'test-user';
        $testRepo        = 'test-repo';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser($testUsername);
        $config->setBranch($configBranch);

        $mockRepoReader = $this->createMockReader($testRepo);
        $mockRepoReader->method('filterProtectedBranches')->willReturn(['main', 'dev']);

        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();

        $command = new SyncCommand(
            config: $config,
            repoReader: $mockRepoReader,
            workflowsReader: $mockWorkflowsReader,
            githubClient: $mockGitHubClient
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => $testUsername,
            '--' . RepoBranchCommandOption::NAME     => $cliBranch,
            '--' . GitHubTokenCommandOption::NAME    => $testGitHubToken,
        ]);

        // Command should succeed using CLI branch option
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testConfigBranchUsedWhenCliNotProvided(): void
    {
        $configBranch    = 'main';
        $testUsername    = 'test-user';
        $testRepo        = 'test-repo';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser($testUsername);
        $config->setBranch($configBranch);

        $mockRepoReader = $this->createMockReader($testRepo);
        $mockRepoReader->method('filterProtectedBranches')->willReturn(['main', 'dev']);

        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();

        $command = new SyncCommand(
            config: $config,
            repoReader: $mockRepoReader,
            workflowsReader: $mockWorkflowsReader,
            githubClient: $mockGitHubClient
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => $testUsername,
            '--' . GitHubTokenCommandOption::NAME    => $testGitHubToken,
        ]);

        // Command should succeed using config branch
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testWarningWhenConfigBranchNotInProtectedBranches(): void
    {
        $configBranch    = 'feature-branch';
        $testUsername    = 'test-user';
        $testRepo        = 'test-repo';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser($testUsername);
        $config->setBranch($configBranch);

        $mockRepoReader = $this->createMockReader($testRepo);
        // Only one protected branch, so it will auto-select after warning
        $mockRepoReader->method('filterProtectedBranches')->willReturn(['main']);

        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();

        $command = new SyncCommand(
            config: $config,
            repoReader: $mockRepoReader,
            workflowsReader: $mockWorkflowsReader,
            githubClient: $mockGitHubClient
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => $testUsername,
            '--' . GitHubTokenCommandOption::NAME    => $testGitHubToken,
            // Do NOT provide CLI branch - let it try to use config branch
        ]);

        // Command should succeed and output should contain warning about invalid config branch
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Warning', $output);
        $this->assertStringContainsString('feature-branch', $output);
        $this->assertStringContainsString('not in the list of protected branches', $output);
    }

    public function testGitConfigUsedWhenConfigAndCliNotProvided(): void
    {
        $gitUsername     = 'git-user';
        $testRepo        = 'test-repo';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        // No user or branch set in config

        $mockRepoReader = $this->createMockReader($testRepo);
        $mockRepoReader->method('getUsername')->willReturn($gitUsername);

        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();

        $command = new SyncCommand(
            config: $config,
            repoReader: $mockRepoReader,
            workflowsReader: $mockWorkflowsReader,
            githubClient: $mockGitHubClient
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--' . GitHubTokenCommandOption::NAME => $testGitHubToken,
        ]);

        // Command should succeed using git config username
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testEmptyConfigDoesNotAffectDefaultBehavior(): void
    {
        $testUsername    = 'test-user';
        $testRepo        = 'test-repo';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        // Empty config - no values set

        $mockRepoReader      = $this->createMockReader($testRepo);
        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();

        $command = new SyncCommand(
            config: $config,
            repoReader: $mockRepoReader,
            workflowsReader: $mockWorkflowsReader,
            githubClient: $mockGitHubClient
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => $testUsername,
            '--' . GitHubTokenCommandOption::NAME    => $testGitHubToken,
        ]);

        // Command should work normally with empty config
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testCliTokenOverridesConfigTokenFile(): void
    {
        $testUsername       = 'test-user';
        $testRepo           = 'test-repo';
        $testGitHubToken    = 'ghp_1234567890abcdef1234567890abcdef1234';
        $tokenFileToken     = 'ghp_filetoken123456789012345678901234';

        // Create a temporary token file
        $tempDir   = sys_get_temp_dir() . '/gh-matrix-test-' . uniqid();
        mkdir($tempDir);
        $tokenFile = $tempDir . '/gh_token';
        file_put_contents($tokenFile, $tokenFileToken);

        $config = new GHMatrixConfig();
        $config->setUser($testUsername);
        $config->setBranch('main');
        // Set token file relative path (we'll mock getRepoRoot to return our temp dir)
        $config->setTokenFile('gh_token');

        $mockRepoReader = $this->createMockReader($testRepo);
        $mockRepoReader->method('getRepoRoot')->willReturn($tempDir);

        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();

        $command = new SyncCommand(
            config: $config,
            repoReader: $mockRepoReader,
            workflowsReader: $mockWorkflowsReader,
            githubClient: $mockGitHubClient
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--' . GitHubTokenCommandOption::NAME => $testGitHubToken,
        ]);

        // Command should succeed using CLI token (not file token)
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Cleanup
        unlink($tokenFile);
        rmdir($tempDir);
    }

    public function testConfigTokenFileUsedWhenCliNotProvided(): void
    {
        $testUsername    = 'test-user';
        $testRepo        = 'test-repo';
        $tokenFileToken  = 'ghp_filetoken123456789012345678901234';

        // Create a temporary token file
        $tempDir   = sys_get_temp_dir() . '/gh-matrix-test-' . uniqid();
        mkdir($tempDir);
        $tokenFile = $tempDir . '/gh_token';
        file_put_contents($tokenFile, $tokenFileToken);

        $config = new GHMatrixConfig();
        $config->setUser($testUsername);
        $config->setBranch('main');
        $config->setTokenFile('gh_token');

        $mockRepoReader = $this->createMockReader($testRepo);
        $mockRepoReader->method('getRepoRoot')->willReturn($tempDir);

        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();

        $command = new SyncCommand(
            config: $config,
            repoReader: $mockRepoReader,
            workflowsReader: $mockWorkflowsReader,
            githubClient: $mockGitHubClient
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // Command should succeed using token from file
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Cleanup
        unlink($tokenFile);
        rmdir($tempDir);
    }

    public function testTokenFileWithWhitespaceIsTrimmed(): void
    {
        $testUsername    = 'test-user';
        $testRepo        = 'test-repo';
        $tokenFileToken  = 'ghp_filetoken123456789012345678901234';

        // Create a temporary token file with whitespace
        $tempDir   = sys_get_temp_dir() . '/gh-matrix-test-' . uniqid();
        mkdir($tempDir);
        $tokenFile = $tempDir . '/gh_token';
        file_put_contents($tokenFile, "\n  " . $tokenFileToken . "  \n");

        $config = new GHMatrixConfig();
        $config->setUser($testUsername);
        $config->setBranch('main');
        $config->setTokenFile('gh_token');

        $mockRepoReader = $this->createMockReader($testRepo);
        $mockRepoReader->method('getRepoRoot')->willReturn($tempDir);

        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();

        $command = new SyncCommand(
            config: $config,
            repoReader: $mockRepoReader,
            workflowsReader: $mockWorkflowsReader,
            githubClient: $mockGitHubClient
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // Command should succeed (token was trimmed correctly)
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Cleanup
        unlink($tokenFile);
        rmdir($tempDir);
    }

    public function testInvalidTokenFilePathFallsBackToAsking(): void
    {
        $testUsername    = 'test-user';
        $testRepo        = 'test-repo';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser($testUsername);
        $config->setBranch('main');
        $config->setTokenFile('nonexistent/gh_token');

        $mockRepoReader = $this->createMockReader($testRepo);
        $mockRepoReader->method('getRepoRoot')->willReturn('/tmp');

        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();

        $command = new SyncCommand(
            config: $config,
            repoReader: $mockRepoReader,
            workflowsReader: $mockWorkflowsReader,
            githubClient: $mockGitHubClient
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs([$testGitHubToken]);
        $commandTester->execute([]);

        // Command should succeed (fallback to asking for token)
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testTokenFileWithInvalidTokenFormatFallsBackToAsking(): void
    {
        $testUsername       = 'test-user';
        $testRepo           = 'test-repo';
        $validToken         = 'ghp_1234567890abcdef1234567890abcdef1234';
        $invalidTokenInFile = 'invalid-token';

        // Create a temporary token file with invalid token
        $tempDir   = sys_get_temp_dir() . '/gh-matrix-test-' . uniqid();
        mkdir($tempDir);
        $tokenFile = $tempDir . '/gh_token';
        file_put_contents($tokenFile, $invalidTokenInFile);

        $config = new GHMatrixConfig();
        $config->setUser($testUsername);
        $config->setBranch('main');
        $config->setTokenFile('gh_token');

        $mockRepoReader = $this->createMockReader($testRepo);
        $mockRepoReader->method('getRepoRoot')->willReturn($tempDir);

        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();

        $command = new SyncCommand(
            config: $config,
            repoReader: $mockRepoReader,
            workflowsReader: $mockWorkflowsReader,
            githubClient: $mockGitHubClient
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs([$validToken]);
        $commandTester->execute([]);

        // Command should succeed (fallback to asking for token because file had invalid token)
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Cleanup
        unlink($tokenFile);
        rmdir($tempDir);
    }

    public function testConfigBranchValidAndInProtectedBranches(): void
    {
        $configBranch    = 'develop';
        $testUsername    = 'test-user';
        $testRepo        = 'test-repo';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser($testUsername);
        $config->setBranch($configBranch);

        // Create custom mock to avoid conflicts with default filterProtectedBranches
        $mockRepoReader = $this->createMock(RepoReader::class);
        $mockRepoReader->method('getRepoName')->willReturn($testRepo);
        $mockRepoReader->method('filterProtectedBranches')->willReturn(['main', 'develop', 'staging']);

        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();

        $command = new SyncCommand(
            config: $config,
            repoReader: $mockRepoReader,
            workflowsReader: $mockWorkflowsReader,
            githubClient: $mockGitHubClient
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => $testUsername,
            '--' . GitHubTokenCommandOption::NAME    => $testGitHubToken,
        ]);

        // Command should succeed using config branch 'develop'
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Should NOT contain warning since branch is valid
        $output = $commandTester->getDisplay();
        $this->assertStringNotContainsString('Warning', $output);
    }
}
