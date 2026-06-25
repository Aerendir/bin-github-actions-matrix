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

use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\WorkflowsDirCommandOption;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class WorkflowsDirCommandOptionTest extends TestCase
{
    public const string WORKFLOWS_DIR_START = '<<<WORKFLOWS_DIR ';
    public const string WORKFLOWS_DIR_END   = '>>>';

    private WorkflowsDirCommandOption $workflowsDirCommandOption;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowsDirCommandOption = new WorkflowsDirCommandOption();
    }

    public function testGetValueOrNullWithValueProvidedReturnsTheProvidedValue(): void
    {
        $testWorkflowsDir = '/srv/app/.github/workflows';
        $expectedOutput   = sprintf('%s%s%s', self::WORKFLOWS_DIR_START, $testWorkflowsDir, self::WORKFLOWS_DIR_END);

        $command       = $this->createCommandForGetValueOrNull();
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . $this->workflowsDirCommandOption::NAME => $testWorkflowsDir,
        ]);

        $this->assertStringContainsString($expectedOutput, $commandTester->getDisplay());
    }

    public function testGetValueOrNullWithoutValueProvidedReturnsNull(): void
    {
        $expectedOutput = sprintf('%s%s%s', self::WORKFLOWS_DIR_START, '', self::WORKFLOWS_DIR_END);

        $command       = $this->createCommandForGetValueOrNull();
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertStringContainsString($expectedOutput, $commandTester->getDisplay());
    }

    private function createCommandForGetValueOrNull(): Command
    {
        return new class extends Command {
            private readonly WorkflowsDirCommandOption $workflowsDirCommandOption;

            public function __construct()
            {
                parent::__construct('test-get-option-workflows-dir-or-null');
                $this->workflowsDirCommandOption = new WorkflowsDirCommandOption();
            }

            #[\Override]
            protected function configure(): void
            {
                $this->addOption(
                    WorkflowsDirCommandOption::NAME,
                    WorkflowsDirCommandOption::SHORTCUT,
                    InputOption::VALUE_OPTIONAL,
                    'The folder that directly contains the workflow "*.yml" files.',
                );
            }

            #[\Override]
            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $workflowsDir = $this->workflowsDirCommandOption->getValueOrNull($input) ?? '';

                $output->writeln(sprintf('%s%s%s', WorkflowsDirCommandOptionTest::WORKFLOWS_DIR_START, $workflowsDir, WorkflowsDirCommandOptionTest::WORKFLOWS_DIR_END));

                return Command::SUCCESS;
            }
        };
    }
}
