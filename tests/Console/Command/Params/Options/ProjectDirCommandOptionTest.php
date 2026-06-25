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

use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\ProjectDirCommandOption;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class ProjectDirCommandOptionTest extends TestCase
{
    public const string PROJECT_DIR_START = '<<<PROJECT_DIR ';
    public const string PROJECT_DIR_END   = '>>>';

    private ProjectDirCommandOption $projectDirCommandOption;

    #[\Override]
    protected function setUp(): void
    {
        $this->projectDirCommandOption = new ProjectDirCommandOption();
    }

    public function testGetValueOrNullWithValueProvidedReturnsTheProvidedValue(): void
    {
        $testProjectDir = '/srv/app';
        $expectedOutput = sprintf('%s%s%s', self::PROJECT_DIR_START, $testProjectDir, self::PROJECT_DIR_END);

        $command       = $this->createCommandForGetValueOrNull();
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . $this->projectDirCommandOption::NAME => $testProjectDir,
        ]);

        $this->assertStringContainsString($expectedOutput, $commandTester->getDisplay());
    }

    public function testGetValueOrNullWithoutValueProvidedReturnsNull(): void
    {
        $expectedOutput = sprintf('%s%s%s', self::PROJECT_DIR_START, '', self::PROJECT_DIR_END);

        $command       = $this->createCommandForGetValueOrNull();
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertStringContainsString($expectedOutput, $commandTester->getDisplay());
    }

    private function createCommandForGetValueOrNull(): Command
    {
        return new class extends Command {
            private readonly ProjectDirCommandOption $projectDirCommandOption;

            public function __construct()
            {
                parent::__construct('test-get-option-project-dir-or-null');
                $this->projectDirCommandOption = new ProjectDirCommandOption();
            }

            #[\Override]
            protected function configure(): void
            {
                $this->addOption(
                    ProjectDirCommandOption::NAME,
                    ProjectDirCommandOption::SHORTCUT,
                    InputOption::VALUE_OPTIONAL,
                    'The project root that contains the ".github/workflows" folder.',
                );
            }

            #[\Override]
            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $projectDir = $this->projectDirCommandOption->getValueOrNull($input) ?? '';

                $output->writeln(sprintf('%s%s%s', ProjectDirCommandOptionTest::PROJECT_DIR_START, $projectDir, ProjectDirCommandOptionTest::PROJECT_DIR_END));

                return Command::SUCCESS;
            }
        };
    }
}
