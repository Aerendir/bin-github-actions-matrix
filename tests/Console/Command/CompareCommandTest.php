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
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CompareCommandTest extends CommandTestCase
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
        $application->addCommand($command);

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
}
