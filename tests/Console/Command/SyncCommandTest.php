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

use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\GitHubTokenCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\GitHubUsernameCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\SyncCommand;
use Github\Client;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SyncCommandTest extends CommandTestCase
{
    private const string TEST_USERNAME = 'Aerendir';
    private const string TEST_REPO     = 'test-repo';
    private const string TEST_TOKEN    = 'ghp_1234567890abcdef1234567890abcdef1234';

    public function testExecute(): void
    {
        $commandTester = $this->createSyncCommandTester();

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
            // --force skips the confirmation, so the output is the plain plan + result.
            '--force'                                => true,
        ]);

        $expectedOutput = <<<'OUTPUT'
            Protection rules sync
            =====================

            Removing the following combinations from the protection rules:
             - phpcs (8.2)
             - rector (8.2)
            Adding the following combinations to the protection rules:
             - phpcs (8.4)
             - rector (8.4)

             [OK] Sync completed
            OUTPUT;

        $output = $commandTester->getDisplay();

        $this->assertEquals($expectedOutput, trim($output));
    }

    public function testExecuteDryRunShowsThePlanWithoutApplying(): void
    {
        $commandTester = $this->createSyncCommandTester();

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
            '--dry-run'                              => true,
        ]);

        $output = $commandTester->getDisplay();

        // The plan is shown...
        $this->assertStringContainsString('phpcs (8.4)', $output);
        $this->assertStringContainsString('Dry run', $output);
        // ...but nothing is applied and no confirmation is asked.
        $this->assertStringNotContainsString('Sync completed', $output);
        $this->assertStringNotContainsString('Apply these changes', $output);
    }

    public function testExecuteAppliesWhenConfirmationIsAccepted(): void
    {
        $commandTester = $this->createSyncCommandTester();
        $commandTester->setInputs(['yes']);

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
        ]);

        $this->assertStringContainsString('Sync completed', $commandTester->getDisplay());
    }

    public function testExecuteAbortsWhenConfirmationIsDeclined(): void
    {
        $commandTester = $this->createSyncCommandTester();
        $commandTester->setInputs(['no']);

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Aborted', $output);
        $this->assertStringNotContainsString('Sync completed', $output);
    }

    private function createSyncCommandTester(): CommandTester
    {
        $mockRepoReader      = $this->createMockReader(self::TEST_REPO);
        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();
        $this->updateMockGitHubClient($mockGitHubClient, self::TEST_USERNAME, self::TEST_REPO);

        $command = new SyncCommand(repoReader: $mockRepoReader, workflowsReader: $mockWorkflowsReader, githubClient: $mockGitHubClient);

        $application = new Application();
        $application->addCommand($command);

        return new CommandTester($command);
    }

    private function updateMockGitHubClient(Client&MockObject $client, string $repoUsername, string $repoName): void
    {
        $mockGitHubClient = parent::createMockGitHubClient();
        $mockProtection   = $mockGitHubClient->api('repo')->protection();

        $mockProtection->method('removeStatusChecksContexts')->with($repoUsername, $repoName, 'dev', [
            'contexts' => [
                'phpcs (8.3)',
                'phpcs (8.4)',
                'rector (8.3)',
                'rector (8.4)',
            ],
        ]);

        $mockProtection->method('addStatusChecksContexts')->with($repoUsername, $repoName, 'dev', [
            'phpcs (8.4)',
            'rector (8.4)',
        ]);
    }
}
