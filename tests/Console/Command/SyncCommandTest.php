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
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\RepoBranchCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\SyncCommand;
use Aerendir\Bin\GitHubActionsMatrix\Workflow\Finder;
use Aerendir\Bin\GitHubActionsMatrix\Workflow\Reader as WorkflowsReader;
use Github\Api\Repo;
use Github\Api\Repository\Protection;
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

    public function testCheckReturnsZeroWhenInSync(): void
    {
        // The remote contexts exactly match the local required checks: no drift.
        $commandTester = $this->createCheckCommandTester([
            'phpcs (8.3)',
            'phpcs (8.4)',
            'rector (8.3)',
            'rector (8.4)',
        ]);

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
            '--check'                                => true,
        ]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('In sync', $commandTester->getDisplay());
    }

    public function testCheckReturnsOneWhenDriftIsDetected(): void
    {
        $commandTester = $this->createSyncCommandTester();

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
            '--check'                                => true,
        ]);

        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('Drift detected', $commandTester->getDisplay());
    }

    public function testCheckReturnsTwoOnError(): void
    {
        $commandTester = $this->createFailingCommandTester();

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
            '--check'                                => true,
        ]);

        $this->assertSame(2, $commandTester->getStatusCode());
    }

    public function testExecuteSurfacesNonDerivableContextWarnings(): void
    {
        $commandTester = $this->createSyncCommandTesterWithNonDerivableJob();

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
            '--dry-run'                              => true,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('cannot be derived', $output);
        $this->assertStringContainsString('dynamic', $output);
        $this->assertStringContainsString('addRequiredCheck()', $output);
    }

    public function testExecuteReportsSkippedWorkflowsAsInfoNotWarning(): void
    {
        $commandTester = $this->createSyncCommandTesterWithSkippedWorkflow();

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
            '--dry-run'                              => true,
        ]);

        $output = $commandTester->getDisplay();

        // The schedule-only workflow is surfaced to the user...
        $this->assertStringContainsString('excluded from the required-checks', $output);
        $this->assertStringContainsString('Nightly', $output);
        // ...as a plain informational line, not as an alert block (neither [INFO] nor [WARNING]).
        $this->assertStringNotContainsString('[INFO]', $output);
        $this->assertStringNotContainsString('[WARNING]', $output);
    }

    public function testErrorPropagatesWhenNotInCheckMode(): void
    {
        $commandTester = $this->createFailingCommandTester();

        $this->expectException(\RuntimeException::class);
        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
        ]);
    }

    public function testInteractiveBranch403ShowsExplanatoryMessageAndExitsTwoInCheckMode(): void
    {
        $mockProtection = $this->createMock(Protection::class);

        $mockRepo = $this->createMock(Repo::class);
        $mockRepo->method('branches')->willThrowException(
            new \RuntimeException('Resource not accessible by personal access token', 403)
        );
        $mockRepo->method('protection')->willReturn($mockProtection);

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('authenticate');
        $mockClient->method('api')->willReturn($mockRepo);

        $command = new SyncCommand(
            repoReader: $this->createMockReader(self::TEST_REPO),
            workflowsReader: $this->createMockWorkflowsReader(),
            githubClient: $mockClient,
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
            '--check'                                => true,
        ]);

        $this->assertSame(2, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Contents: Read', $output);
        $this->assertStringContainsString('--branch', $output);
        $this->assertStringContainsString('setBranch(', $output);
    }

    public function testInteractiveBranch403PropagatesWithCodeTwoInNormalMode(): void
    {
        $mockProtection = $this->createMock(Protection::class);

        $mockRepo = $this->createMock(Repo::class);
        $mockRepo->method('branches')->willThrowException(
            new \RuntimeException('Resource not accessible by personal access token', 403)
        );
        $mockRepo->method('protection')->willReturn($mockProtection);

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('authenticate');
        $mockClient->method('api')->willReturn($mockRepo);

        $command = new SyncCommand(
            repoReader: $this->createMockReader(self::TEST_REPO),
            workflowsReader: $this->createMockWorkflowsReader(),
            githubClient: $mockClient,
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(2);
        $this->expectExceptionMessageMatches('/Contents: Read/');

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
        ]);
    }

    /**
     * A non-403 error while listing the branches (e.g. a server error) must not be dressed up as the
     * Contents: Read hint: it is rethrown as-is.
     */
    public function testBranchesNon403ErrorPropagatesUnchanged(): void
    {
        $mockRepo = $this->createMock(Repo::class);
        $mockRepo->method('branches')->willThrowException(
            new \RuntimeException('Internal Server Error', 500)
        );

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('authenticate');
        $mockClient->method('api')->willReturn($mockRepo);

        $command = new SyncCommand(
            repoReader: $this->createMockReader(self::TEST_REPO),
            workflowsReader: $this->createMockWorkflowsReader(),
            githubClient: $mockClient,
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Internal Server Error');

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
        ]);
    }

    public function testMissingBranchProtection404ShowsExplanatoryMessageAndExitsTwoInCheckMode(): void
    {
        $mockProtection = $this->createMock(Protection::class);
        $mockProtection->method('show')->willThrowException(
            new \RuntimeException('Branch not protected', 404)
        );

        $mockRepo = $this->createMock(Repo::class);
        // The branch is provided on the CLI, so the branches must not be listed.
        $mockRepo->expects($this->never())->method('branches');
        $mockRepo->method('protection')->willReturn($mockProtection);

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('authenticate');
        $mockClient->method('api')->willReturn($mockRepo);

        $command = new SyncCommand(
            repoReader: $this->createMockReader(self::TEST_REPO),
            workflowsReader: $this->createMockWorkflowsReader(),
            githubClient: $mockClient,
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
            '--' . RepoBranchCommandOption::NAME     => 'ghost-branch',
            '--check'                                => true,
        ]);

        $this->assertSame(2, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('ghost-branch', $output);
        $this->assertStringContainsString('does not exist', $output);
        $this->assertStringContainsString('no branch protection', $output);
        $this->assertStringContainsString('--branch', $output);
        $this->assertStringContainsString('setBranch(', $output);
    }

    public function testMissingBranchProtection404PropagatesWithCodeTwoInNormalMode(): void
    {
        $mockProtection = $this->createMock(Protection::class);
        $mockProtection->method('show')->willThrowException(
            new \RuntimeException('Branch not protected', 404)
        );

        $mockRepo = $this->createMock(Repo::class);
        $mockRepo->expects($this->never())->method('branches');
        $mockRepo->method('protection')->willReturn($mockProtection);

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('authenticate');
        $mockClient->method('api')->willReturn($mockRepo);

        $command = new SyncCommand(
            repoReader: $this->createMockReader(self::TEST_REPO),
            workflowsReader: $this->createMockWorkflowsReader(),
            githubClient: $mockClient,
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(2);
        $this->expectExceptionMessageMatches('/does not exist .* or has no branch protection/s');

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
            '--' . RepoBranchCommandOption::NAME     => 'ghost-branch',
        ]);
    }

    /**
     * A non-404 error while reading the branch protection (e.g. a server error) must be rethrown as-is,
     * not turned into the "branch does not exist" message.
     */
    public function testBranchProtectionNon404ErrorPropagatesUnchanged(): void
    {
        $mockProtection = $this->createMock(Protection::class);
        $mockProtection->method('show')->willThrowException(
            new \RuntimeException('Internal Server Error', 500)
        );

        $mockRepo = $this->createMock(Repo::class);
        $mockRepo->expects($this->never())->method('branches');
        $mockRepo->method('protection')->willReturn($mockProtection);

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('authenticate');
        $mockClient->method('api')->willReturn($mockRepo);

        $command = new SyncCommand(
            repoReader: $this->createMockReader(self::TEST_REPO),
            workflowsReader: $this->createMockWorkflowsReader(),
            githubClient: $mockClient,
        );

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Internal Server Error');

        $commandTester->execute([
            '--' . GitHubUsernameCommandOption::NAME => self::TEST_USERNAME,
            '--' . GitHubTokenCommandOption::NAME    => self::TEST_TOKEN,
            '--' . RepoBranchCommandOption::NAME     => 'main',
        ]);
    }

    /**
     * Builds a tester whose workflows include a job with a dynamic (`fromJson`) matrix, so the read step
     * records a non-derivable-context warning that the command must surface to the user.
     */
    private function createSyncCommandTesterWithNonDerivableJob(): CommandTester
    {
        $workflowContent = <<<YAML
            name: CI
            on: [push]
            jobs:
              dynamic:
                strategy:
                  matrix:
                    os: \${{ fromJson(needs.setup.outputs.os) }}
                steps:
                  - uses: actions/checkout@v3
            YAML;

        $fileInfo = $this->createTempFile($workflowContent, 'ci');

        $finder = $this->createMock(Finder::class);
        $finder->method('getWorkflows')->willReturn(new \ArrayIterator([$fileInfo]));

        $command = new SyncCommand(
            repoReader: $this->createMockReader(self::TEST_REPO),
            workflowsReader: new WorkflowsReader($finder),
            githubClient: $this->createMockGitHubClient(),
        );

        $application = new Application();
        $application->addCommand($command);

        return new CommandTester($command);
    }

    /**
     * Builds a tester whose workflows include a schedule-only workflow (not PR-eligible), so the read
     * step records an informational note that the command must surface as info — not as a warning.
     */
    private function createSyncCommandTesterWithSkippedWorkflow(): CommandTester
    {
        $pushWorkflow = <<<YAML
            name: PHP CS
            on: [push]
            jobs:
              phpcs:
                strategy:
                  matrix:
                    php: [ '8.2', '8.3' ]
                steps:
                  - uses: actions/checkout@v3
            YAML;

        $scheduleWorkflow = <<<YAML
            name: Nightly
            on:
              schedule:
                - cron: '0 0 * * *'
            jobs:
              nightly:
                runs-on: ubuntu-latest
                steps:
                  - uses: actions/checkout@v3
            YAML;

        $pushFile     = $this->createTempFile($pushWorkflow, 'phpcs');
        $scheduleFile = $this->createTempFile($scheduleWorkflow, 'nightly');

        $finder = $this->createMock(Finder::class);
        $finder->method('getWorkflows')->willReturn(new \ArrayIterator([$pushFile, $scheduleFile]));

        $command = new SyncCommand(
            repoReader: $this->createMockReader(self::TEST_REPO),
            workflowsReader: new WorkflowsReader($finder),
            githubClient: $this->createMockGitHubClient(),
        );

        $application = new Application();
        $application->addCommand($command);

        return new CommandTester($command);
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

    /**
     * Builds a tester whose remote branch protection exposes exactly the given required-check contexts,
     * so the test controls whether the local workflows are in sync or drifting.
     *
     * @param array<string> $remoteContexts
     */
    private function createCheckCommandTester(array $remoteContexts): CommandTester
    {
        $mockProtection = $this->createMock(Protection::class);
        $mockProtection->method('show')->willReturn([
            'required_status_checks' => ['contexts' => $remoteContexts],
        ]);

        $mockRepo = $this->createMock(Repo::class);
        $mockRepo->method('protection')->willReturn($mockProtection);
        $mockRepo->method('branches')->willReturn([]);

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('authenticate');
        $mockClient->method('api')->willReturn($mockRepo);

        $command = new SyncCommand(
            repoReader: $this->createMockReader(self::TEST_REPO),
            workflowsReader: $this->createMockWorkflowsReader(),
            githubClient: $mockClient
        );

        $application = new Application();
        $application->addCommand($command);

        return new CommandTester($command);
    }

    /**
     * Builds a tester whose GitHub client fails during authentication, to exercise the error paths.
     */
    private function createFailingCommandTester(): CommandTester
    {
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('authenticate')->willThrowException(new \RuntimeException('Authentication failed'));

        $command = new SyncCommand(
            repoReader: $this->createMockReader(self::TEST_REPO),
            workflowsReader: $this->createMockWorkflowsReader(),
            githubClient: $mockClient
        );

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
