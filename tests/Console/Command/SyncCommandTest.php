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
    public function testExecute(): void
    {
        $testUsername    = 'Aerendir';
        $testRepo        = 'test-repo';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $mockRepoReader      = $this->createMockReader($testRepo);
        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();
        $this->updateMockGitHubClient($mockGitHubClient, $testUsername, $testRepo);

        $command = new SyncCommand(repoReader: $mockRepoReader, workflowsReader: $mockWorkflowsReader, githubClient: $mockGitHubClient);

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => $testUsername,
            '--' . GitHubTokenCommandOption::NAME    => $testGitHubToken,
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
