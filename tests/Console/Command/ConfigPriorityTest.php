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
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\RepoNameCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\SyncCommand;
use Aerendir\Bin\GitHubActionsMatrix\Repo\Reader as RepoReader;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use function Aerendir\Bin\GitHubActionsMatrix\Tests\Functions\chdir;
use function Aerendir\Bin\GitHubActionsMatrix\Tests\Functions\file_put_contents;
use function Aerendir\Bin\GitHubActionsMatrix\Tests\Functions\getcwd;
use function Aerendir\Bin\GitHubActionsMatrix\Tests\Functions\mkdir;
use function Aerendir\Bin\GitHubActionsMatrix\Tests\Functions\putenv;
use function Aerendir\Bin\GitHubActionsMatrix\Tests\Functions\rmdir;
use function Aerendir\Bin\GitHubActionsMatrix\Tests\Functions\unlink;

class ConfigPriorityTest extends CommandTestCase
{
    private const string TOKEN_ENV_VAR = 'GH_MATRIX_TOKEN';

    /** @var string|null Original CWD saved before tests that call chdir() */
    private ?string $originalCwd = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Guarantee isolation: the token env var must never leak in from the host or a previous test.
        putenv(self::TOKEN_ENV_VAR);
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore CWD if it was changed by a test
        if (null !== $this->originalCwd) {
            chdir($this->originalCwd);
            $this->originalCwd = null;
        }

        // Never let the token env var leak into the next test.
        putenv(self::TOKEN_ENV_VAR);
    }

    public function testEnvTokenUsedWhenCliNotProvided(): void
    {
        $envToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser('test-user');
        $config->setBranch('main');

        $command = new SyncCommand(
            config: $config,
            repoReader: $this->createMockReader('test-repo'),
            workflowsReader: $this->createMockWorkflowsReader(),
            githubClient: $this->createMockGitHubClient()
        );

        $application = new Application();
        $application->addCommand($command);

        putenv(self::TOKEN_ENV_VAR . '=' . $envToken);

        $commandTester = new CommandTester($command);
        // No --token and no interactive input: the token must be taken from the environment variable.
        $commandTester->execute([]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testCliTokenTakesPrecedenceOverEnvToken(): void
    {
        $cliToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser('test-user');
        $config->setBranch('main');

        $command = new SyncCommand(
            config: $config,
            repoReader: $this->createMockReader('test-repo'),
            workflowsReader: $this->createMockWorkflowsReader(),
            githubClient: $this->createMockGitHubClient()
        );

        $application = new Application();
        $application->addCommand($command);

        // The env token is invalid; the command only succeeds if the CLI option is read first (priority 1).
        putenv(self::TOKEN_ENV_VAR . '=invalid-env-token');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--' . GitHubTokenCommandOption::NAME => $cliToken,
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testInvalidEnvTokenFallsBackToAsking(): void
    {
        $promptToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser('test-user');
        $config->setBranch('main');

        $command = new SyncCommand(
            config: $config,
            repoReader: $this->createMockReader('test-repo'),
            workflowsReader: $this->createMockWorkflowsReader(),
            githubClient: $this->createMockGitHubClient()
        );

        $application = new Application();
        $application->addCommand($command);

        // A malformed env token must be ignored, falling back to the interactive prompt.
        putenv(self::TOKEN_ENV_VAR . '=not-a-valid-token');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs([$promptToken]);
        $commandTester->execute([]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testEmptyEnvTokenFallsBackToAsking(): void
    {
        $promptToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser('test-user');
        $config->setBranch('main');

        $command = new SyncCommand(
            config: $config,
            repoReader: $this->createMockReader('test-repo'),
            workflowsReader: $this->createMockWorkflowsReader(),
            githubClient: $this->createMockGitHubClient()
        );

        $application = new Application();
        $application->addCommand($command);

        // An empty env token must be ignored, falling back to the interactive prompt.
        putenv(self::TOKEN_ENV_VAR . '=');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs([$promptToken]);
        $commandTester->execute([]);

        $this->assertEquals(0, $commandTester->getStatusCode());
    }

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
        $tokenFileToken     = 'ghp_1234567890123456789012345678901234ab';

        // Create a temporary token file
        $tempDir   = sys_get_temp_dir() . '/gh-matrix-test-' . uniqid();
        mkdir($tempDir, 0777, true);
        $tokenFile = $tempDir . '/gh_token';
        file_put_contents($tokenFile, $tokenFileToken);

        try {
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
        } finally {
            // Cleanup
            if (file_exists($tokenFile)) {
                unlink($tokenFile);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    public function testConfigTokenFileUsedWhenCliNotProvided(): void
    {
        $testUsername    = 'test-user';
        $testRepo        = 'test-repo';
        $tokenFileToken  = 'ghp_1234567890123456789012345678901234ab';

        // Create a temporary token file
        $tempDir   = sys_get_temp_dir() . '/gh-matrix-test-' . uniqid();
        mkdir($tempDir, 0777, true);
        $tokenFile = $tempDir . '/gh_token';
        file_put_contents($tokenFile, $tokenFileToken);

        try {
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
        } finally {
            // Cleanup
            if (file_exists($tokenFile)) {
                unlink($tokenFile);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    public function testTokenFileWithWhitespaceIsTrimmed(): void
    {
        $testUsername    = 'test-user';
        $testRepo        = 'test-repo';
        $tokenFileToken  = 'ghp_1234567890123456789012345678901234ab';

        // Create a temporary token file with whitespace
        $tempDir   = sys_get_temp_dir() . '/gh-matrix-test-' . uniqid();
        mkdir($tempDir, 0777, true);
        $tokenFile = $tempDir . '/gh_token';
        file_put_contents($tokenFile, "\n  " . $tokenFileToken . "  \n");

        try {
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
        } finally {
            // Cleanup
            if (file_exists($tokenFile)) {
                unlink($tokenFile);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
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

    public function testPathTraversalAttackIsBlocked(): void
    {
        $testUsername    = 'test-user';
        $testRepo        = 'test-repo';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        // Create a temporary directory structure
        $tempDir = sys_get_temp_dir() . '/gh-matrix-test-' . uniqid();
        mkdir($tempDir, 0777, true);

        try {
            // Try to access a file outside the repo using path traversal
            $config = new GHMatrixConfig();
            $config->setUser($testUsername);
            $config->setBranch('main');
            $config->setTokenFile('../../../etc/passwd'); // Path traversal attempt

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
            $commandTester->setInputs([$testGitHubToken]);
            $commandTester->execute([]);

            // Command should succeed (path traversal blocked, fallback to asking for token)
            $this->assertEquals(0, $commandTester->getStatusCode());
        } finally {
            // Cleanup
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    public function testTokenFileWithInvalidTokenFormatFallsBackToAsking(): void
    {
        $testUsername       = 'test-user';
        $testRepo           = 'test-repo';
        $validToken         = 'ghp_1234567890abcdef1234567890abcdef1234';
        $invalidTokenInFile = 'invalid-token';

        // Create a temporary token file with invalid token
        $tempDir   = sys_get_temp_dir() . '/gh-matrix-test-' . uniqid();
        mkdir($tempDir, 0777, true);
        $tokenFile = $tempDir . '/gh_token';
        file_put_contents($tokenFile, $invalidTokenInFile);

        try {
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
        } finally {
            // Cleanup
            if (file_exists($tokenFile)) {
                unlink($tokenFile);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    public function testProjectDirFromConfigDrivesWorkflowDiscovery(): void
    {
        $testUsername    = 'test-user';
        $testRepo        = 'test-repo';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        // A temp project that contains .github/workflows/*.yml
        $projectDir   = sys_get_temp_dir() . '/gh-matrix-project-' . uniqid();
        $workflowsDir = $projectDir . '/.github/workflows';
        mkdir($workflowsDir, 0777, true);
        $workflowFile = $workflowsDir . '/ci.yml';
        file_put_contents($workflowFile, <<<YAML
            name: PHP CS
            on: [push]
            jobs:
              phpcs:
                strategy:
                  fail-fast: false
                  matrix:
                    php: [ '8.3', '8.4' ]
                steps:
                  - name: Checkout
                    uses: actions/checkout@v3
            YAML);

        try {
            $config = new GHMatrixConfig();
            $config->setUser($testUsername);
            $config->setBranch('master');
            $config->setProjectDir($projectDir);

            $mockRepoReader = $this->createMock(RepoReader::class);
            $mockRepoReader->method('getRepoName')->willReturn($testRepo);
            $mockRepoReader->method('filterProtectedBranches')->willReturn(['master']);
            // Simulate "no git": the workflows must be discovered via setProjectDir(), not the git root.
            $mockRepoReader->method('getRepoRoot')->willThrowException(new \RuntimeException('not a git repository'));

            $mockGitHubClient = $this->createMockGitHubClient();

            // NOTE: no workflowsReader injected -> the lazy Finder must discover the workflows via setProjectDir().
            $command = new SyncCommand(
                config: $config,
                repoReader: $mockRepoReader,
                githubClient: $mockGitHubClient
            );

            $application = new Application();
            $application->addCommand($command);

            $commandTester = new CommandTester($command);
            $commandTester->execute([
                '--' . GitHubTokenCommandOption::NAME => $testGitHubToken,
            ]);

            // Succeeds only if the lazy Finder located the workflows under <projectDir>/.github/workflows.
            $this->assertEquals(0, $commandTester->getStatusCode());
        } finally {
            // Cleanup (innermost first)
            if (file_exists($workflowFile)) {
                unlink($workflowFile);
            }
            if (is_dir($workflowsDir)) {
                rmdir($workflowsDir);
            }
            if (is_dir($projectDir . '/.github')) {
                rmdir($projectDir . '/.github');
            }
            if (is_dir($projectDir)) {
                rmdir($projectDir);
            }
        }
    }

    public function testTokenBasePrefersProjectDirOverGitRoot(): void
    {
        $testUsername   = 'test-user';
        $testRepo       = 'test-repo';
        $tokenFileToken = 'ghp_1234567890123456789012345678901234ab';

        // projectDir CONTAINS the token file; the git root deliberately does NOT.
        $projectDir = sys_get_temp_dir() . '/gh-matrix-proj-' . uniqid();
        $gitRoot    = sys_get_temp_dir() . '/gh-matrix-git-' . uniqid();
        mkdir($projectDir, 0777, true);
        mkdir($gitRoot, 0777, true);
        $tokenFile = $projectDir . '/gh_token';
        file_put_contents($tokenFile, $tokenFileToken);

        try {
            $config = new GHMatrixConfig();
            $config->setUser($testUsername);
            $config->setBranch('master');
            $config->setTokenFile('gh_token');
            $config->setProjectDir($projectDir);

            $mockRepoReader = $this->createMock(RepoReader::class);
            $mockRepoReader->method('getRepoName')->willReturn($testRepo);
            $mockRepoReader->method('filterProtectedBranches')->willReturn(['master']);
            // Git root is available but lacks the token file: if it were preferred, resolution would fail.
            $mockRepoReader->method('getRepoRoot')->willReturn($gitRoot);

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

            // Succeeds only if the token file was resolved against projectDir (not the git root).
            $this->assertEquals(0, $commandTester->getStatusCode());
        } finally {
            // Cleanup
            if (file_exists($tokenFile)) {
                unlink($tokenFile);
            }
            if (is_dir($projectDir)) {
                rmdir($projectDir);
            }
            if (is_dir($gitRoot)) {
                rmdir($gitRoot);
            }
        }
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

    public function testCliRepoNameOptionOverridesConfigRepoName(): void
    {
        $cliRepoName     = 'cli-repo';
        $configRepoName  = 'config-repo';
        $testUsername    = 'test-user';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser($testUsername);
        $config->setBranch('main');
        $config->setRepoName($configRepoName);

        $mockRepoReader      = $this->createMockReader('git-repo');
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
            '--' . RepoNameCommandOption::NAME    => $cliRepoName,
            '--' . GitHubTokenCommandOption::NAME => $testGitHubToken,
        ]);

        // Command should succeed using CLI repo name
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testConfigRepoNameUsedWhenCliNotProvided(): void
    {
        $configRepoName  = 'config-repo';
        $testUsername    = 'test-user';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser($testUsername);
        $config->setBranch('main');
        $config->setRepoName($configRepoName);

        // Mock reader without a getRepoName override (it would throw if called, but config should short-circuit)
        $mockRepoReader = $this->createMock(RepoReader::class);
        $mockRepoReader->method('getRepoName')->willReturn('git-repo');
        $mockRepoReader->method('filterProtectedBranches')->willReturn(['master']);

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

        // Command should succeed using config repo name
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testGitRepoNameUsedWhenConfigAndCliNotProvided(): void
    {
        $gitRepoName     = 'git-repo';
        $testUsername    = 'test-user';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser($testUsername);
        $config->setBranch('main');
        // No repoName in config

        $mockRepoReader = $this->createMock(RepoReader::class);
        $mockRepoReader->method('getRepoName')->willReturn($gitRepoName);
        $mockRepoReader->method('filterProtectedBranches')->willReturn(['master']);

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

        // Command should succeed using git remote repo name
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testRepoNamePromptsWhenGitThrowsAndNoConfigOrCli(): void
    {
        $promptedRepoName = 'prompted-repo';
        $testUsername     = 'test-user';
        $testGitHubToken  = 'ghp_1234567890abcdef1234567890abcdef1234';

        $config = new GHMatrixConfig();
        $config->setUser($testUsername);
        $config->setBranch('main');
        // No repoName in config

        $mockRepoReader = $this->createMock(RepoReader::class);
        // Simulate environment without git remote
        $mockRepoReader->method('getRepoName')->willThrowException(new \RuntimeException('No git remote'));
        $mockRepoReader->method('filterProtectedBranches')->willReturn(['master']);

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
        $commandTester->setInputs([$promptedRepoName]);
        $commandTester->execute([
            '--' . GitHubTokenCommandOption::NAME => $testGitHubToken,
        ]);

        // Command should succeed: repo name was obtained via interactive prompt
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testGetRepoNameThrowsWhenInputMissingAndGitUnavailable(): void
    {
        $config = new GHMatrixConfig(); // no repo name configured

        $mockRepoReader = $this->createMock(RepoReader::class);
        $mockRepoReader->method('getRepoName')->willThrowException(new \RuntimeException('not a git repository'));

        $command = new SyncCommand(config: $config, repoReader: $mockRepoReader);

        // getRepoName() is also used as a parameterless getter; with no CLI/config/git and no I/O it must fail loudly.
        $method = new \ReflectionMethod($command, 'getRepoName');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You must pass input/output/helper to resolve the repo name.');

        $method->invoke($command);
    }

    public function testTokenFileReadWithoutGit(): void
    {
        $testUsername   = 'test-user';
        $testRepo       = 'test-repo';
        $tokenFileToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        // Create a temporary directory and place the token file in it
        $tempDir   = sys_get_temp_dir() . '/gh-matrix-test-' . uniqid();
        mkdir($tempDir, 0777, true);
        $tokenFile = $tempDir . '/gh_token';
        file_put_contents($tokenFile, $tokenFileToken);

        try {
            $config = new GHMatrixConfig();
            $config->setUser($testUsername);
            $config->setBranch('main');
            $config->setTokenFile('gh_token');

            // Simulate an environment without git: getRepoRoot() throws.
            // Use createMock() directly so phpstan recognises the full MockObject interface.
            $mockRepoReader = $this->createMock(RepoReader::class);
            $mockRepoReader->method('getRepoName')->willReturn($testRepo);
            $mockRepoReader->method('filterProtectedBranches')->willReturn(['master']);
            $mockRepoReader->method('getRepoRoot')->willThrowException(new \RuntimeException('No git repository'));

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

            // Change to the temp dir so the CWD fallback finds the token file
            $this->originalCwd = getcwd();
            chdir($tempDir);

            $commandTester = new CommandTester($command);
            $commandTester->execute([]);

            // Command should succeed: token was read via the CWD fallback (no git available)
            $this->assertEquals(0, $commandTester->getStatusCode());
        } finally {
            if (file_exists($tokenFile)) {
                unlink($tokenFile);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    public function testTraversalRejectedWithCwdBase(): void
    {
        $testUsername    = 'test-user';
        $testRepo        = 'test-repo';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $tempDir = sys_get_temp_dir() . '/gh-matrix-test-' . uniqid();
        mkdir($tempDir, 0777, true);

        try {
            // Attempt to traverse outside the CWD base using a path traversal attack
            $config = new GHMatrixConfig();
            $config->setUser($testUsername);
            $config->setBranch('main');
            $config->setTokenFile('../../../etc/passwd');

            // Simulate an environment without git: getRepoRoot() throws.
            // Use createMock() directly so phpstan recognises the full MockObject interface.
            $mockRepoReader = $this->createMock(RepoReader::class);
            $mockRepoReader->method('getRepoName')->willReturn($testRepo);
            $mockRepoReader->method('filterProtectedBranches')->willReturn(['master']);
            $mockRepoReader->method('getRepoRoot')->willThrowException(new \RuntimeException('No git repository'));

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

            // Change to the temp dir so the CWD is the base
            $this->originalCwd = getcwd();
            chdir($tempDir);

            $commandTester = new CommandTester($command);
            $commandTester->setInputs([$testGitHubToken]);
            $commandTester->execute([]);

            // Path traversal must be rejected; command succeeds by falling back to the interactive prompt
            $this->assertEquals(0, $commandTester->getStatusCode());
        } finally {
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    public function testTokenFilePointingToDirectoryFallsBackToAsking(): void
    {
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $baseDir = sys_get_temp_dir() . '/gh-matrix-tokendir-' . uniqid();
        mkdir($baseDir, 0777, true);
        // A DIRECTORY named like the token file: the path resolves but is not a file.
        $tokenDir = $baseDir . '/gh_token';
        mkdir($tokenDir, 0777, true);

        try {
            $config = new GHMatrixConfig();
            $config->setUser('test-user');
            $config->setBranch('master');
            $config->setTokenFile('gh_token');

            $mockRepoReader = $this->createMock(RepoReader::class);
            $mockRepoReader->method('getRepoName')->willReturn('test-repo');
            $mockRepoReader->method('filterProtectedBranches')->willReturn(['master']);
            $mockRepoReader->method('getRepoRoot')->willReturn($baseDir);

            $command = new SyncCommand(
                config: $config,
                repoReader: $mockRepoReader,
                workflowsReader: $this->createMockWorkflowsReader(),
                githubClient: $this->createMockGitHubClient()
            );

            $application = new Application();
            $application->addCommand($command);

            $commandTester = new CommandTester($command);
            $commandTester->setInputs([$testGitHubToken]);
            $commandTester->execute([]);

            // Succeeds by falling back to asking, because the token "file" is a directory.
            $this->assertEquals(0, $commandTester->getStatusCode());
        } finally {
            if (is_dir($tokenDir)) {
                rmdir($tokenDir);
            }
            if (is_dir($baseDir)) {
                rmdir($baseDir);
            }
        }
    }

    public function testWorkflowsDirFromCliDrivesWorkflowDiscovery(): void
    {
        [$projectDir, $workflowsDir] = $this->createTempProjectWithWorkflow();

        try {
            $config = new GHMatrixConfig();
            $config->setUser('test-user');
            $config->setBranch('master');

            $command = new SyncCommand(
                config: $config,
                repoReader: $this->createGitlessRepoReader('test-repo'),
                githubClient: $this->createMockGitHubClient()
            );

            $application = new Application();
            $application->addCommand($command);

            $commandTester = new CommandTester($command);
            $commandTester->execute([
                '--' . GitHubTokenCommandOption::NAME => 'ghp_1234567890abcdef1234567890abcdef1234',
                '--workflows-dir'                     => $workflowsDir,
            ]);

            $this->assertEquals(0, $commandTester->getStatusCode());
        } finally {
            $this->removeTempProject($projectDir);
        }
    }

    public function testWorkflowsDirFromConfigDrivesWorkflowDiscovery(): void
    {
        [$projectDir, $workflowsDir] = $this->createTempProjectWithWorkflow();

        try {
            $config = new GHMatrixConfig();
            $config->setUser('test-user');
            $config->setBranch('master');
            $config->setWorkflowsDir($workflowsDir);

            $command = new SyncCommand(
                config: $config,
                repoReader: $this->createGitlessRepoReader('test-repo'),
                githubClient: $this->createMockGitHubClient()
            );

            $application = new Application();
            $application->addCommand($command);

            $commandTester = new CommandTester($command);
            $commandTester->execute([
                '--' . GitHubTokenCommandOption::NAME => 'ghp_1234567890abcdef1234567890abcdef1234',
            ]);

            $this->assertEquals(0, $commandTester->getStatusCode());
        } finally {
            $this->removeTempProject($projectDir);
        }
    }

    public function testProjectDirFromCliDrivesWorkflowDiscovery(): void
    {
        [$projectDir] = $this->createTempProjectWithWorkflow();

        try {
            $config = new GHMatrixConfig();
            $config->setUser('test-user');
            $config->setBranch('master');

            $command = new SyncCommand(
                config: $config,
                repoReader: $this->createGitlessRepoReader('test-repo'),
                githubClient: $this->createMockGitHubClient()
            );

            $application = new Application();
            $application->addCommand($command);

            $commandTester = new CommandTester($command);
            $commandTester->execute([
                '--' . GitHubTokenCommandOption::NAME => 'ghp_1234567890abcdef1234567890abcdef1234',
                '--project-dir'                       => $projectDir,
            ]);

            $this->assertEquals(0, $commandTester->getStatusCode());
        } finally {
            $this->removeTempProject($projectDir);
        }
    }

    /**
     * Creates a temp project containing `.github/workflows/ci.yml`.
     *
     * @return array{0: string, 1: string} [projectDir, workflowsDir]
     */
    private function createTempProjectWithWorkflow(): array
    {
        $projectDir   = sys_get_temp_dir() . '/gh-matrix-wf-' . uniqid();
        $workflowsDir = $projectDir . '/.github/workflows';
        mkdir($workflowsDir, 0777, true);
        file_put_contents($workflowsDir . '/ci.yml', <<<YAML
            name: PHP CS
            on: [push]
            jobs:
              phpcs:
                strategy:
                  fail-fast: false
                  matrix:
                    php: [ '8.3', '8.4' ]
                steps:
                  - name: Checkout
                    uses: actions/checkout@v3
            YAML);

        return [$projectDir, $workflowsDir];
    }

    private function removeTempProject(string $projectDir): void
    {
        $workflowFile = $projectDir . '/.github/workflows/ci.yml';
        if (file_exists($workflowFile)) {
            unlink($workflowFile);
        }

        foreach ([$projectDir . '/.github/workflows', $projectDir . '/.github', $projectDir] as $dir) {
            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
    }

    /**
     * A RepoReader mock that resolves the repo name and protected branches but reports "no git"
     * (so the workflows location must be resolved declaratively, not from the git root).
     */
    private function createGitlessRepoReader(string $repoName): RepoReader
    {
        $mockRepoReader = $this->createMock(RepoReader::class);
        $mockRepoReader->method('getRepoName')->willReturn($repoName);
        $mockRepoReader->method('filterProtectedBranches')->willReturn(['master']);
        $mockRepoReader->method('getRepoRoot')->willThrowException(new \RuntimeException('not a git repository'));

        return $mockRepoReader;
    }
}
