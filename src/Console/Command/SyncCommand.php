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

use Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\CheckCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\DryRunCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\ForceCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\GitHubTokenCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\GitHubUsernameCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\ProjectDirCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\RepoBranchCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\RepoNameCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\WorkflowsDirCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Repo\Reader as RepoReader;
use Aerendir\Bin\GitHubActionsMatrix\ValueObject\JobsCollection;
use Aerendir\Bin\GitHubActionsMatrix\Workflow\Comparator;
use Aerendir\Bin\GitHubActionsMatrix\Workflow\Reader as WorkflowsReader;
use Github\Client;
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
    public const string COMMAND_NAME = 'sync';

    /** Exit code returned by --check when the branch protection drifts from the workflows. */
    private const int EXIT_DRIFT = 1;

    /** Exit code returned by --check when the check itself fails (bad token, network, parse error). */
    private const int EXIT_ERROR = 2;

    public function __construct(
        ?GHMatrixConfig $config = null,
        ?GitHubUsernameCommandOption $gitHubUsernameCommandOption = null,
        ?GitHubTokenCommandOption $gitHubTokenCommandOption = null,
        ?RepoBranchCommandOption $repoBranchCommandOption = null,
        ?RepoNameCommandOption $repoNameCommandOption = null,
        ?RepoReader $repoReader = null,
        ?WorkflowsReader $workflowsReader = null,
        ?Comparator $comparator = null,
        ?Client $githubClient = null,
        ?ProjectDirCommandOption $projectDirCommandOption = null,
        ?WorkflowsDirCommandOption $workflowsDirCommandOption = null,
        private readonly ForceCommandOption $forceCommandOption = new ForceCommandOption(),
        private readonly DryRunCommandOption $dryRunCommandOption = new DryRunCommandOption(),
        private readonly CheckCommandOption $checkCommandOption = new CheckCommandOption(),
    ) {
        parent::__construct(
            $config,
            $gitHubUsernameCommandOption,
            $gitHubTokenCommandOption,
            $repoBranchCommandOption,
            $repoNameCommandOption,
            $repoReader,
            $workflowsReader,
            $comparator,
            $githubClient,
            $projectDirCommandOption,
            $workflowsDirCommandOption,
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Protection rules sync');

        $isCheck = $this->isCheck($input);

        try {
            $this->init($input, $output);
        } catch (\Throwable $throwable) {
            // In --check mode (CI gate) report the failure as a distinct exit code instead of bubbling up.
            if ($isCheck) {
                $io->error($throwable->getMessage());

                return self::EXIT_ERROR;
            }

            throw $throwable;
        }

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

        // --check is a CI gate: read-only, no prompt, with the drift encoded in the exit code.
        if ($isCheck) {
            if ([] === $remoteCombinationsToRemove && [] === $remoteCombinationsToCreate) {
                $io->success('In sync: the branch protection matches the workflows.');

                return self::SUCCESS;
            }

            $io->warning('Drift detected: the branch protection is not aligned with the workflows.');

            return self::EXIT_DRIFT;
        }

        // --dry-run is a read-only preview: the plan was printed above, stop before any change.
        if ($this->isDryRun($input)) {
            $io->note('Dry run: no changes were applied.');

            return self::SUCCESS;
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

        $this->addOption(ForceCommandOption::NAME, ForceCommandOption::SHORTCUT, InputOption::VALUE_NONE, 'Apply the changes without asking for confirmation.');
        $this->addOption(DryRunCommandOption::NAME, null, InputOption::VALUE_NONE, 'Show what would change without touching the branch protection (read-only preview).');
        $this->addOption(CheckCommandOption::NAME, null, InputOption::VALUE_NONE, 'CI gate: read-only, exit 0 if aligned, 1 if drift, 2 on error. Implies --dry-run.');
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
        if ($this->isForce($input)) {
            return true;
        }

        return $io->confirm('Apply these changes to the branch protection rules?', false);
    }

    private function isForce(InputInterface $input): bool
    {
        return $this->forceCommandOption->isEnabled($input);
    }

    private function isDryRun(InputInterface $input): bool
    {
        return $this->dryRunCommandOption->isEnabled($input);
    }

    private function isCheck(InputInterface $input): bool
    {
        return $this->checkCommandOption->isEnabled($input);
    }
}
