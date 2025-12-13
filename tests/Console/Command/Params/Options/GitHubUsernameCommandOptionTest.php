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

use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\GitHubUsernameCommandOption;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class GitHubUsernameCommandOptionTest extends TestCase
{
    final public const string GITHUB_USERNAME_START = '<<<GITHUB_USERNAME ';
    final public const string GITHUB_USERNAME_END   = '>>>';

    private GitHubUsernameCommandOption $gitHubUsernameCommandOption;

    protected function setUp(): void
    {
        $this->gitHubUsernameCommandOption = new GitHubUsernameCommandOption();
    }

    public function testGetValueOrAskWithValidGitHubUsernameProvidedReturnsTheProvidedGitHubUsername(): void
    {
        $testGitHubUsername  = 'Aerendir';
        $expectedOutput      = sprintf('%s%s%s', GitHubUsernameCommandOptionTest::GITHUB_USERNAME_START, $testGitHubUsername, GitHubUsernameCommandOptionTest::GITHUB_USERNAME_END);

        $command = $this->createCommandForGetValueOrAsk();

        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . $this->gitHubUsernameCommandOption::NAME => $testGitHubUsername,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    public function testGetValueOrAskWithoutGitHubUsernameProvidedAsksForTheGitHubUsername(): void
    {
        $testGitHubUsername  = 'Aerendir';
        $expectedOutput      = sprintf('%s%s%s', GitHubUsernameCommandOptionTest::GITHUB_USERNAME_START, $testGitHubUsername, GitHubUsernameCommandOptionTest::GITHUB_USERNAME_END);

        $command     = $this->createCommandForGetValueOrAsk();
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs([$testGitHubUsername]);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    public function testGetValueOrNullWithValidGitHubUsernameProvidedReturnsTheProvidedGitHubUsername(): void
    {
        $testGitHubUsername  = 'ghp_1234567890abcdef1234567890abcdef1234';
        $expectedOutput      = sprintf('%s%s%s', GitHubUsernameCommandOptionTest::GITHUB_USERNAME_START, $testGitHubUsername, GitHubUsernameCommandOptionTest::GITHUB_USERNAME_END);

        $command       = $this->createCommandForGetValueOrNull();
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . $this->gitHubUsernameCommandOption::NAME => $testGitHubUsername,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    public function testGetValueOrNullWithoutAGitHubUsernameProvidedReturnsNull(): void
    {
        $expectedOutput = sprintf('%s%s%s', GitHubUsernameCommandOptionTest::GITHUB_USERNAME_START, '', GitHubUsernameCommandOptionTest::GITHUB_USERNAME_END);

        $command       = $this->createCommandForGetValueOrNull();
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString($expectedOutput, $output);
    }

    private function createCommandForGetValueOrAsk(): Command
    {
        return new class extends Command {
            private readonly GitHubUsernameCommandOption $GitHubUsernameCommandOption;

            public function __construct()
            {
                parent::__construct('test-get-option-git-hub-username-or-ask');
                $this->GitHubUsernameCommandOption = new GitHubUsernameCommandOption();
            }

            protected function configure(): void
            {
                $this->addOption(
                    GitHubUsernameCommandOption::NAME,
                    GitHubUsernameCommandOption::SHORTCUT,
                    // This is to test the checks inside the class.
                    // The real implementations should use `InputOption::VALUE_REQUIRED`.
                    InputOption::VALUE_OPTIONAL,
                    'The GitHub Username to connect to the repo.',
                );
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $questionHelper = $this->getHelper('question');

                if (false === $questionHelper instanceof QuestionHelper) {
                    throw new \RuntimeException(sprintf('The helper %s is not available.', QuestionHelper::class));
                }

                $gitHubUsername = $this->GitHubUsernameCommandOption->getValueOrAsk($input, $output, $questionHelper);

                // For the purpose of the test, we just output the GITHUB_USERNAME.
                $output->writeln(sprintf('%s%s%s', GitHubUsernameCommandOptionTest::GITHUB_USERNAME_START, $gitHubUsername, GitHubUsernameCommandOptionTest::GITHUB_USERNAME_END));

                return Command::SUCCESS;
            }
        };
    }

    private function createCommandForGetValueOrNull(): Command
    {
        return new class extends Command {
            private readonly GitHubUsernameCommandOption $GitHubUsernameCommandOption;

            public function __construct()
            {
                parent::__construct('test-get-option-git-hub-username-or-null');
                $this->GitHubUsernameCommandOption = new GitHubUsernameCommandOption();
            }

            protected function configure(): void
            {
                $this->addOption(
                    GitHubUsernameCommandOption::NAME,
                    GitHubUsernameCommandOption::SHORTCUT,
                    // This is to test the checks inside the class.
                    // The real implementations should use `InputOption::VALUE_REQUIRED`.
                    InputOption::VALUE_OPTIONAL,
                    'The GitHub Username to connect to the repo.',
                );
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $gitHubUsername = $this->GitHubUsernameCommandOption->getValueOrNull($input) ?? '';

                // For the purpose of the test, we just output the GITHUB_USERNAME.
                $output->writeln(sprintf('%s%s%s', GitHubUsernameCommandOptionTest::GITHUB_USERNAME_START, $gitHubUsername, GitHubUsernameCommandOptionTest::GITHUB_USERNAME_END));

                return Command::SUCCESS;
            }
        };
    }
}
