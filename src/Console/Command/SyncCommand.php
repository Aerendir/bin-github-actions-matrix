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

namespace Aerendir\Bin\GitHubActionsMatrix\Console\Command;

use Aerendir\Bin\GitHubActionsMatrix\ValueObject\JobsCollection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[AsCommand(name: self::COMMAND_NAME, description: 'Sync workflows configured in the repo with the current matrix of protection rules on GitHub.')]
final class SyncCommand extends AbstractCommand
{
    public const string COMMAND_NAME           = 'sync';
    private const string FORCE_OPTION_NAME     = 'force';
    private const string FORCE_OPTION_SHORTCUT = 'f';

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Protection rules sync');

        $this->init($input, $output);

        $repoUsername               = $this->getRepoUsername();
        $repoName                   = $this->getRepoName();
        $localJobs                  = $this->getLocalJobs();
        $protection                 = $this->getProtection();
        $remoteCombinationsToRemove = $this->getCombinationsToRemove();
        $remoteCombinationsToCreate = $this->collectCombinationsToCreate($localJobs);

        if ([] !== $remoteCombinationsToRemove) {
            $io->writeln('Removing the following combinations from the protection rules:');
            foreach ($remoteCombinationsToRemove as $combination) {
                $io->writeln(' - ' . $combination);
            }
        }

        if ([] !== $remoteCombinationsToCreate) {
            $io->writeln('Adding the following combinations to the protection rules:');
            foreach ($remoteCombinationsToCreate as $combination) {
                $io->writeln(' - ' . $combination);
            }
        }

        // Ask for confirmation before touching the branch protection: removals in particular are
        // destructive. The confirmation can be skipped with --force (e.g. in CI).
        if (false === $this->shouldApply($input, $io)) {
            $io->warning('Aborted: no changes were applied.');

            return self::SUCCESS;
        }

        if ([] !== $remoteCombinationsToRemove) {
            $protection->removeStatusChecksContexts($repoUsername, $repoName, $this->branchName, ['contexts' => $remoteCombinationsToRemove]);
        }

        if ([] !== $remoteCombinationsToCreate) {
            $protection->addStatusChecksContexts($repoUsername, $repoName, $this->branchName, $remoteCombinationsToCreate);
        }

        $io->success('Sync completed');

        return self::SUCCESS;
    }

    #[\Override]
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(self::FORCE_OPTION_NAME, self::FORCE_OPTION_SHORTCUT, InputOption::VALUE_NONE, 'Apply the changes without asking for confirmation.');
    }

    /**
     * @return array<int, string>
     */
    private function collectCombinationsToCreate(JobsCollection $localJobs): array
    {
        $remoteCombinationsToCreate = [];
        foreach ($localJobs->getJobs() as $job) {
            foreach ($job->getMatrix()->getCombinations() as $combination) {
                if ($combination->isToSync() && false === $combination->isOptional()) {
                    $remoteCombinationsToCreate[] = (string) $combination;
                }
            }
        }

        return $remoteCombinationsToCreate;
    }

    private function shouldApply(InputInterface $input, SymfonyStyle $io): bool
    {
        if (true === $input->getOption(self::FORCE_OPTION_NAME)) {
            return true;
        }

        return $io->confirm('Apply these changes to the branch protection rules?', false);
    }
}
