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

use Aerendir\Bin\GitHubActionsMatrix\Console\Command\CompareCommand;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\GitHubTokenCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\GitHubUsernameCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Repo\Reader as RepoReader;
use Aerendir\Bin\GitHubActionsMatrix\Workflow\Finder;
use Aerendir\Bin\GitHubActionsMatrix\Workflow\Reader as WorkflowsReader;
use Github\Api\Repo;
use Github\Api\Repository\Protection;
use Github\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Finder\SplFileInfo;

use function Safe\file_put_contents;

class CompareCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $testUsername    = 'Aerendir';
        $testRepo        = 'test-repo';
        $testGitHubToken = 'ghp_1234567890abcdef1234567890abcdef1234';

        $mockRepoReader      = $this->createMockReader($testRepo);
        $mockWorkflowsReader = $this->createMockWorkflowsReader();
        $mockGitHubClient    = $this->createMockGitHubClient();

        $command = new CompareCommand(repoReader: $mockRepoReader, workflowsReader: $mockWorkflowsReader, githubClient: $mockGitHubClient);

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => $testUsername,
            '--' . GitHubTokenCommandOption::NAME    => $testGitHubToken,
        ]);

        $expectedOutput = <<<'OUTPUT'
            Protection rules comparison matrix
            ==================================

            +- phpcs.yaml > PHP CS > phpcs --+
            | Status Check | Action    | php |
            +--------------+-----------+-----+
            | phpcs (8.3)  | ✔ Nothing | 8.3 |
            | phpcs (8.4)  | ⇄ Sync    | 8.4 |
            +--------------+-----------+-----+

            +- rector.yaml > Rector > re... -+
            | Status Check | Action    | php |
            +--------------+-----------+-----+
            | rector (8.3) | ✔ Nothing | 8.3 |
            | rector (8.4) | ⇄ Sync    | 8.4 |
            +--------------+-----------+-----+

            +- Required Checks on ... -+
            | Status check | Action    |
            +--------------+-----------+
            | phpcs (8.2)  | ✖ Remove  |
            | phpcs (8.3)  | ✔ Nothing |
            | rector (8.2) | ✖ Remove  |
            | rector (8.3) | ✔ Nothing |
            +--------------+-----------+
            OUTPUT;

        $output = $commandTester->getDisplay();

        $this->assertEquals($expectedOutput, trim($output));
    }

    private function createMockReader(string $testRepo): RepoReader
    {
        $mockReader = $this->createMock(RepoReader::class);
        $mockReader->method('getRepoName')->willReturn($testRepo);

        return $mockReader;
    }

    private function createMockWorkflowsReader(): WorkflowsReader
    {
        $phpCsWorkflowContent = <<<YAML
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
            YAML;

        $rectorWorkflowContent = <<<YAML
            name: Rector
            on: [push]
            jobs:
              rector:
                strategy:
                  fail-fast: false
                  matrix:
                    php: [ '8.3', '8.4' ]
                steps:
                  - name: Checkout
                    uses: actions/checkout@v3
            YAML;

        $phpCsFileInfo  = $this->createTempFile($phpCsWorkflowContent, 'phpcs');
        $rectorFileInfo = $this->createTempFile($rectorWorkflowContent, 'rector');

        $finder = $this->createMock(Finder::class);
        $finder->method('getWorkflows')->willReturn(new \ArrayIterator([$phpCsFileInfo, $rectorFileInfo]));

        return new WorkflowsReader($finder);
    }

    private function createMockGitHubClient(): Client
    {
        $mockProtection = $this->createMock(Protection::class);
        $mockProtection->method('show')->willReturn([
            'required_status_checks' => [
                'contexts' => [
                    'phpcs (8.2)',
                    'phpcs (8.3)',
                    'rector (8.2)',
                    'rector (8.3)',
                ],
            ],
        ]);

        $mockRepo = $this->createMock(Repo::class);
        $mockRepo->method('protection')->willReturn($mockProtection);

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('authenticate');
        $mockClient->method('api')->with('repo')->willReturn($mockRepo);

        return $mockClient;
    }

    private function createTempFile(string $content, string $fileName = 'workflow'): SplFileInfo
    {
        $filePathname = sys_get_temp_dir() . sprintf('/%s.yaml', $fileName);

        file_put_contents($filePathname, $content);

        return new SplFileInfo($filePathname, dirname($filePathname), basename($filePathname));
    }
}
