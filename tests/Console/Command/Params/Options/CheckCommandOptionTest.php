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

use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\CheckCommandOption;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class CheckCommandOptionTest extends TestCase
{
    public const string CHECK_START = '<<<CHECK ';
    public const string CHECK_END   = '>>>';

    public function testIsEnabledReturnsTrueWhenTheFlagIsPassed(): void
    {
        $command       = $this->createCommandForIsEnabled();
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . CheckCommandOption::NAME => true,
        ]);

        $this->assertStringContainsString(sprintf('%s%s%s', self::CHECK_START, 'yes', self::CHECK_END), $commandTester->getDisplay());
    }

    public function testIsEnabledReturnsFalseWhenTheFlagIsNotPassed(): void
    {
        $command       = $this->createCommandForIsEnabled();
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertStringContainsString(sprintf('%s%s%s', self::CHECK_START, 'no', self::CHECK_END), $commandTester->getDisplay());
    }

    private function createCommandForIsEnabled(): Command
    {
        return new class extends Command {
            private readonly CheckCommandOption $checkCommandOption;

            public function __construct()
            {
                parent::__construct('test-check-option-is-enabled');
                $this->checkCommandOption = new CheckCommandOption();
            }

            #[\Override]
            protected function configure(): void
            {
                $this->addOption(
                    CheckCommandOption::NAME,
                    null,
                    InputOption::VALUE_NONE,
                    'CI gate: read-only, exit 0 if aligned, 1 if drift, 2 on error. Implies --dry-run.',
                );
            }

            #[\Override]
            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $enabled = $this->checkCommandOption->isEnabled($input) ? 'yes' : 'no';

                $output->writeln(sprintf('%s%s%s', CheckCommandOptionTest::CHECK_START, $enabled, CheckCommandOptionTest::CHECK_END));

                return Command::SUCCESS;
            }
        };
    }
}
