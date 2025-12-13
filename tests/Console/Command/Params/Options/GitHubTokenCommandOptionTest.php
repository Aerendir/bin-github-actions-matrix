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

use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\GitHubTokenCommandOption;
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

class GitHubTokenCommandOptionTest extends TestCase
{
    final public const string GITHUB_TOKEN_START = '<<<GITHUB_TOKEN ';
    final public const string GITHUB_TOKEN_END   = '>>>';

    private GitHubTokenCommandOption $gitHubTokenCommandOption;

    protected function setUp(): void
    {
        $this->gitHubTokenCommandOption = new GitHubTokenCommandOption();
    }

    public function testGetValueOrAskWithValidGitHubTokenProvidedReturnsTheProvidedGitHubToken(): void
    {
        $testGitHubToken  = 'ghp_1234567890abcdef1234567890abcdef1234';
        $expectedOutput   = sprintf('%s%s%s', GitHubTokenCommandOptionTest::GITHUB_TOKEN_START, $testGitHubToken, GitHubTokenCommandOptionTest::GITHUB_TOKEN_END);

        $command = $this->createCommandForGetValueOrAsk();

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . $this->gitHubTokenCommandOption::NAME => $testGitHubToken,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    public function testGetValueOrAskWithoutGitHubTokenProvidedAsksForTheGitHubToken(): void
    {
        $testGitHubToken  = 'ghp_1234567890abcdef1234567890abcdef1234';
        $expectedOutput   = sprintf('%s%s%s', GitHubTokenCommandOptionTest::GITHUB_TOKEN_START, $testGitHubToken, GitHubTokenCommandOptionTest::GITHUB_TOKEN_END);

        $command     = $this->createCommandForGetValueOrAsk();
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs([$testGitHubToken]);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    public function testGetValueOrAskWithInvalidGitHubTokenProvidedThrowsAnException(): void
    {
        $invalidGitHubToken = 'invalid-git-hub-token';

        $command     = $this->createCommandForGetValueOrAsk();
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs([$invalidGitHubToken]);

        // We use the exact message to ensure the exception is triggered by
        // the class and not by the `InputOption::VALUE_REQUIRED`.
        $this->expectException(MissingInputException::class);
        $this->expectExceptionMessage('You must pass a valid token of the repo.');
        $commandTester->execute([]);
    }

    public function testGetValueOrNullWithValidGitHubTokenProvidedReturnsTheProvidedGitHubToken(): void
    {
        $testGitHubToken  = 'ghp_1234567890abcdef1234567890abcdef1234';
        $expectedOutput   = sprintf('%s%s%s', GitHubTokenCommandOptionTest::GITHUB_TOKEN_START, $testGitHubToken, GitHubTokenCommandOptionTest::GITHUB_TOKEN_END);

        $command       = $this->createCommandForGetValueOrNull();
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . $this->gitHubTokenCommandOption::NAME => $testGitHubToken,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    public function testGetValueOrNullWithoutAnGitHubTokenProvidedReturnsNull(): void
    {
        $expectedOutput = sprintf('%s%s%s', GitHubTokenCommandOptionTest::GITHUB_TOKEN_START, '', GitHubTokenCommandOptionTest::GITHUB_TOKEN_END);

        $command       = $this->createCommandForGetValueOrNull();
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    public function testGetValueOrNullWithAnInvalidGitHubTokenProvidedThrowsAnException(): void
    {
        $testGitHubToken = 'invalid-git-hub-token';

        $command       = $this->createCommandForGetValueOrNull();
        $commandTester = new CommandTester($command);

        // We use the exact message to ensure the exception is triggered by
        // the class and not by the `InputOption::VALUE_REQUIRED`.
        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage('The GitHub Token format is invalid.');
        $commandTester->execute([
            '--' . $this->gitHubTokenCommandOption::NAME => $testGitHubToken,
        ]);
    }

    private function createCommandForGetValueOrAsk(): Command
    {
        return new class extends Command {
            private readonly GitHubTokenCommandOption $GitHubTokenCommandOption;

            public function __construct()
            {
                parent::__construct('test-get-option-git-hub-token-or-ask');
                $this->GitHubTokenCommandOption = new GitHubTokenCommandOption();
            }

            protected function configure(): void
            {
                $this->addOption(
                    GitHubTokenCommandOption::NAME,
                    GitHubTokenCommandOption::SHORTCUT,
                    // This is to test the checks inside the class.
                    // The real implementations should use `InputOption::VALUE_REQUIRED`.
                    InputOption::VALUE_OPTIONAL,
                    'The GitHub Token to connect to the repo.',
                );
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $questionHelper = $this->getHelper('question');

                if (false === $questionHelper instanceof QuestionHelper) {
                    throw new \RuntimeException(sprintf('The helper %s is not available.', QuestionHelper::class));
                }

                $gitHubToken = $this->GitHubTokenCommandOption->getValueOrAsk($input, $output, $questionHelper);

                // For the purpose of the test, we just output the GITHUB_TOKEN.
                $output->writeln(sprintf('%s%s%s', GitHubTokenCommandOptionTest::GITHUB_TOKEN_START, $gitHubToken, GitHubTokenCommandOptionTest::GITHUB_TOKEN_END));

                return Command::SUCCESS;
            }
        };
    }

    private function createCommandForGetValueOrNull(): Command
    {
        return new class extends Command {
            private readonly GitHubTokenCommandOption $GitHubTokenCommandOption;

            public function __construct()
            {
                parent::__construct('test-get-option-git-hub-token-or-null');
                $this->GitHubTokenCommandOption = new GitHubTokenCommandOption();
            }

            protected function configure(): void
            {
                $this->addOption(
                    GitHubTokenCommandOption::NAME,
                    GitHubTokenCommandOption::SHORTCUT,
                    // This is to test the checks inside the class.
                    // The real implementations should use `InputOption::VALUE_REQUIRED`.
                    InputOption::VALUE_OPTIONAL,
                    'The GitHub Token to connect to the repo.',
                );
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $gitHubToken = $this->GitHubTokenCommandOption->getValueOrNull($input) ?? '';

                // For the purpose of the test, we just output the GITHUB_TOKEN.
                $output->writeln(sprintf('%s%s%s', GitHubTokenCommandOptionTest::GITHUB_TOKEN_START, $gitHubToken, GitHubTokenCommandOptionTest::GITHUB_TOKEN_END));

                return Command::SUCCESS;
            }
        };
    }
}
