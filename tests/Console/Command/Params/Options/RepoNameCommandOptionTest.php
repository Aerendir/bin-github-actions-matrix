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

use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\RepoNameCommandOption;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class RepoNameCommandOptionTest extends TestCase
{
    final public const string REPO_NAME_START = '<<<REPO_NAME ';
    final public const string REPO_NAME_END   = '>>>';

    private RepoNameCommandOption $repoNameCommandOption;

    protected function setUp(): void
    {
        $this->repoNameCommandOption = new RepoNameCommandOption();
    }

    public function testGetValueOrAskWithValidRepoNameProvidedReturnsTheProvidedRepoName(): void
    {
        $testRepoName   = 'my-repo';
        $expectedOutput = sprintf('%s%s%s', self::REPO_NAME_START, $testRepoName, self::REPO_NAME_END);

        $command = $this->createCommandForGetValueOrAsk();

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . $this->repoNameCommandOption::NAME => $testRepoName,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    public function testGetValueOrAskWithoutRepoNameProvidedAsksForTheRepoName(): void
    {
        $testRepoName   = 'my-repo';
        $expectedOutput = sprintf('%s%s%s', self::REPO_NAME_START, $testRepoName, self::REPO_NAME_END);

        $command     = $this->createCommandForGetValueOrAsk();
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs([$testRepoName]);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    public function testGetValueOrNullWithValidRepoNameProvidedReturnsTheProvidedRepoName(): void
    {
        $testRepoName   = 'my-repo';
        $expectedOutput = sprintf('%s%s%s', self::REPO_NAME_START, $testRepoName, self::REPO_NAME_END);

        $command       = $this->createCommandForGetValueOrNull();
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . $this->repoNameCommandOption::NAME => $testRepoName,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    public function testGetValueOrAskThrowsWhenAllAttemptsAreExhausted(): void
    {
        $command     = $this->createCommandForGetValueOrAsk();
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        // Both answers are empty: rejected by the validator, so the prompt exhausts its attempts and throws.
        $commandTester->setInputs(['', '']);

        $this->expectException(MissingInputException::class);
        $this->expectExceptionMessage('You must pass a valid name of the repo.');

        $commandTester->execute([]);
    }

    public function testGetValueOrNullWithEmptyRepoNameThrows(): void
    {
        $command       = $this->createCommandForGetValueOrNull();
        $commandTester = new CommandTester($command);

        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage('The repo name cannot be empty.');

        $commandTester->execute([
            '--' . $this->repoNameCommandOption::NAME => '   ',
        ]);
    }

    public function testGetValueOrAskRePromptsWhenEmptyValueIsProvided(): void
    {
        $testRepoName   = 'my-repo';
        $expectedOutput = sprintf('%s%s%s', self::REPO_NAME_START, $testRepoName, self::REPO_NAME_END);

        $command     = $this->createCommandForGetValueOrAsk();
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        // First answer is empty (rejected by the validator), the second is valid.
        $commandTester->setInputs(['', $testRepoName]);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    public function testGetValueOrNullWithoutARepoNameProvidedReturnsNull(): void
    {
        $expectedOutput = sprintf('%s%s%s', self::REPO_NAME_START, '', self::REPO_NAME_END);

        $command       = $this->createCommandForGetValueOrNull();
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    private function createCommandForGetValueOrAsk(): Command
    {
        return new class extends Command {
            private readonly RepoNameCommandOption $RepoNameCommandOption;

            public function __construct()
            {
                parent::__construct('test-get-option-repo-name-or-ask');
                $this->RepoNameCommandOption = new RepoNameCommandOption();
            }

            protected function configure(): void
            {
                $this->addOption(
                    RepoNameCommandOption::NAME,
                    RepoNameCommandOption::SHORTCUT,
                    // This is to test the checks inside the class.
                    // The real implementations should use `InputOption::VALUE_REQUIRED`.
                    InputOption::VALUE_OPTIONAL,
                    'The name of the GitHub repository.',
                );
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $questionHelper = $this->getHelper('question');

                if (false === $questionHelper instanceof QuestionHelper) {
                    throw new \RuntimeException(sprintf('The helper %s is not available.', QuestionHelper::class));
                }

                $repoName = $this->RepoNameCommandOption->getValueOrAsk($input, $output, $questionHelper);

                // For the purpose of the test, we just output the REPO_NAME.
                $output->writeln(sprintf('%s%s%s', RepoNameCommandOptionTest::REPO_NAME_START, $repoName, RepoNameCommandOptionTest::REPO_NAME_END));

                return Command::SUCCESS;
            }
        };
    }

    private function createCommandForGetValueOrNull(): Command
    {
        return new class extends Command {
            private readonly RepoNameCommandOption $RepoNameCommandOption;

            public function __construct()
            {
                parent::__construct('test-get-option-repo-name-or-null');
                $this->RepoNameCommandOption = new RepoNameCommandOption();
            }

            protected function configure(): void
            {
                $this->addOption(
                    RepoNameCommandOption::NAME,
                    RepoNameCommandOption::SHORTCUT,
                    // This is to test the checks inside the class.
                    // The real implementations should use `InputOption::VALUE_REQUIRED`.
                    InputOption::VALUE_OPTIONAL,
                    'The name of the GitHub repository.',
                );
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $repoName = $this->RepoNameCommandOption->getValueOrNull($input) ?? '';

                // For the purpose of the test, we just output the REPO_NAME.
                $output->writeln(sprintf('%s%s%s', RepoNameCommandOptionTest::REPO_NAME_START, $repoName, RepoNameCommandOptionTest::REPO_NAME_END));

                return Command::SUCCESS;
            }
        };
    }
}
