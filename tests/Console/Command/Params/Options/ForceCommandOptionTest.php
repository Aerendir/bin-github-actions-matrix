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

use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\ForceCommandOption;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class ForceCommandOptionTest extends TestCase
{
    public const string FORCE_START = '<<<FORCE ';
    public const string FORCE_END   = '>>>';

    public function testIsEnabledReturnsTrueWhenTheFlagIsPassed(): void
    {
        $command       = $this->createCommandForIsEnabled();
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--' . ForceCommandOption::NAME => true,
        ]);

        $this->assertStringContainsString(sprintf('%s%s%s', self::FORCE_START, 'yes', self::FORCE_END), $commandTester->getDisplay());
    }

    public function testIsEnabledReturnsFalseWhenTheFlagIsNotPassed(): void
    {
        $command       = $this->createCommandForIsEnabled();
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertStringContainsString(sprintf('%s%s%s', self::FORCE_START, 'no', self::FORCE_END), $commandTester->getDisplay());
    }

    private function createCommandForIsEnabled(): Command
    {
        return new class extends Command {
            private readonly ForceCommandOption $forceCommandOption;

            public function __construct()
            {
                parent::__construct('test-force-option-is-enabled');
                $this->forceCommandOption = new ForceCommandOption();
            }

            #[\Override]
            protected function configure(): void
            {
                $this->addOption(
                    ForceCommandOption::NAME,
                    ForceCommandOption::SHORTCUT,
                    InputOption::VALUE_NONE,
                    'Apply the changes without asking for confirmation.',
                );
            }

            #[\Override]
            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $enabled = $this->forceCommandOption->isEnabled($input) ? 'yes' : 'no';

                $output->writeln(sprintf('%s%s%s', ForceCommandOptionTest::FORCE_START, $enabled, ForceCommandOptionTest::FORCE_END));

                return Command::SUCCESS;
            }
        };
    }
}
