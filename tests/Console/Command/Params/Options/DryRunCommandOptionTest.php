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

use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\DryRunCommandOption;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class DryRunCommandOptionTest extends TestCase
{
    public const string DRY_RUN_START = '<<<DRY_RUN ';
    public const string DRY_RUN_END   = '>>>';

    public function testIsEnabledReturnsTrueWhenTheFlagIsPassed(): void
    {
        $command       = $this->createCommandForIsEnabled();
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . DryRunCommandOption::NAME => true,
        ]);

        $this->assertStringContainsString(sprintf('%s%s%s', self::DRY_RUN_START, 'yes', self::DRY_RUN_END), $commandTester->getDisplay());
    }

    public function testIsEnabledReturnsFalseWhenTheFlagIsNotPassed(): void
    {
        $command       = $this->createCommandForIsEnabled();
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertStringContainsString(sprintf('%s%s%s', self::DRY_RUN_START, 'no', self::DRY_RUN_END), $commandTester->getDisplay());
    }

    private function createCommandForIsEnabled(): Command
    {
        return new class extends Command {
            private readonly DryRunCommandOption $dryRunCommandOption;

            public function __construct()
            {
                parent::__construct('test-dry-run-option-is-enabled');
                $this->dryRunCommandOption = new DryRunCommandOption();
            }

            #[\Override]
            protected function configure(): void
            {
                $this->addOption(
                    DryRunCommandOption::NAME,
                    null,
                    InputOption::VALUE_NONE,
                    'Show what would change without touching the branch protection (read-only preview).',
                );
            }

            #[\Override]
            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $enabled = $this->dryRunCommandOption->isEnabled($input) ? 'yes' : 'no';

                $output->writeln(sprintf('%s%s%s', DryRunCommandOptionTest::DRY_RUN_START, $enabled, DryRunCommandOptionTest::DRY_RUN_END));

                return Command::SUCCESS;
            }
        };
    }
}
