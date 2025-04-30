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

namespace Aerendir\Bin\GitHubActionsMatrix\Tests\Console\Command\Params\Options;

use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\RepoBranchCommandOption;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class RepoBranchCommandOptionTest extends TestCase
{
    final public const string REPO_BRANCH_START = '<<<REPO_BRANCH ';
    final public const string REPO_BRANCH_END   = '>>>';

    private RepoBranchCommandOption $repoBranchCommandOption;

    protected function setUp(): void
    {
        $this->repoBranchCommandOption = new RepoBranchCommandOption();
    }

    public function testGetValueOrAskWithValidRepoBranchProvidedReturnsTheProvidedRepoBranch(): void
    {
        $testRepoBranch  = 'master';
        $expectedOutput  = sprintf('%s%s%s', RepoBranchCommandOptionTest::REPO_BRANCH_START, $testRepoBranch, RepoBranchCommandOptionTest::REPO_BRANCH_END);

        $command = $this->createCommandForGetValueOrAsk();

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . $this->repoBranchCommandOption::NAME => $testRepoBranch,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    public function testGetValueOrAskWithoutRepoBranchProvidedAsksForTheRepoBranch(): void
    {
        $testRepoBranch  = 'master';
        $expectedOutput  = sprintf('%s%s%s', RepoBranchCommandOptionTest::REPO_BRANCH_START, $testRepoBranch, RepoBranchCommandOptionTest::REPO_BRANCH_END);

        $command     = $this->createCommandForGetValueOrAsk();
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs([$testRepoBranch]);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    public function testGetValueOrNullWithValidRepoBranchProvidedReturnsTheProvidedRepoBranch(): void
    {
        $testRepoBranch  = 'ghp_1234567890abcdef1234567890abcdef1234';
        $expectedOutput  = sprintf('%s%s%s', RepoBranchCommandOptionTest::REPO_BRANCH_START, $testRepoBranch, RepoBranchCommandOptionTest::REPO_BRANCH_END);

        $command       = $this->createCommandForGetValueOrNull();
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . $this->repoBranchCommandOption::NAME => $testRepoBranch,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    public function testGetValueOrNullWithoutARepoBranchProvidedReturnsNull(): void
    {
        $expectedOutput = sprintf('%s%s%s', RepoBranchCommandOptionTest::REPO_BRANCH_START, '', RepoBranchCommandOptionTest::REPO_BRANCH_END);

        $command       = $this->createCommandForGetValueOrNull();
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    private function createCommandForGetValueOrAsk(): Command
    {
        return new class extends Command {
            private readonly RepoBranchCommandOption $RepoBranchCommandOption;

            public function __construct()
            {
                parent::__construct('test-get-option-repo-branch-or-ask');
                $this->RepoBranchCommandOption = new RepoBranchCommandOption();
            }

            protected function configure(): void
            {
                $this->addOption(
                    RepoBranchCommandOption::NAME,
                    RepoBranchCommandOption::SHORTCUT,
                    // This is to test the checks inside the class.
                    // The real implementations should use `InputOption::VALUE_REQUIRED`.
                    InputOption::VALUE_OPTIONAL,
                    'The repo branch to analyse.',
                );
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $questionHelper = $this->getHelper('question');

                if (false === $questionHelper instanceof QuestionHelper) {
                    throw new \RuntimeException(sprintf('The helper %s is not available.', QuestionHelper::class));
                }

                $repoBranch = $this->RepoBranchCommandOption->getValueOrAsk($input, $output, $questionHelper, ['master', 'dev']);

                // For the purpose of the test, we just output the REPO_BRANCH.
                $output->writeln(sprintf('%s%s%s', RepoBranchCommandOptionTest::REPO_BRANCH_START, $repoBranch, RepoBranchCommandOptionTest::REPO_BRANCH_END));

                return Command::SUCCESS;
            }
        };
    }

    private function createCommandForGetValueOrNull(): Command
    {
        return new class extends Command {
            private readonly RepoBranchCommandOption $RepoBranchCommandOption;

            public function __construct()
            {
                parent::__construct('test-get-option-repo-branch-or-null');
                $this->RepoBranchCommandOption = new RepoBranchCommandOption();
            }

            protected function configure(): void
            {
                $this->addOption(
                    RepoBranchCommandOption::NAME,
                    RepoBranchCommandOption::SHORTCUT,
                    // This is to test the checks inside the class.
                    // The real implementations should use `InputOption::VALUE_REQUIRED`.
                    InputOption::VALUE_OPTIONAL,
                    'The repo branch to analyse.',
                );
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $repoBranch = $this->RepoBranchCommandOption->getValueOrNull($input) ?? '';

                // For the purpose of the test, we just output the REPO_BRANCH.
                $output->writeln(sprintf('%s%s%s', RepoBranchCommandOptionTest::REPO_BRANCH_START, $repoBranch, RepoBranchCommandOptionTest::REPO_BRANCH_END));

                return Command::SUCCESS;
            }
        };
    }
}
