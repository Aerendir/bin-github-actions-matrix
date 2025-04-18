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

use Aerendir\Bin\GitHubActionsMatrix\ValueObject\Combination;
use Aerendir\Bin\GitHubActionsMatrix\ValueObject\JobsCollection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[AsCommand(name: self::COMMAND_NAME, description: 'Compare the workflows configured in the repo with the current matrix of protection rules on GitHub.')]
final class CompareCommand extends AbstractCommand
{
    public const string COMMAND_NAME = 'compare';
    private const string  THICK      = "\xE2\x9C\x94";
    private const string  CROSS      = "\xE2\x9C\x96";
    private const string SYNC        = "\xE2\x87\x84"; // ⇄

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Protection rules comparison matrix');

        $this->init($input, $output);

        $localJobs                  = $this->getLocalJobs();
        $remoteJobsIds              = $this->getRemoteJobsIds();
        $remoteCombinationsToRemove = $this->getCombinationsToRemove();

        $this->printResult($output, $localJobs, $remoteJobsIds, $remoteCombinationsToRemove);

        return self::SUCCESS;
    }

    /**
     * @param array<string> $remoteJobsIds
     * @param array<string> $remoteJobsToRemove
     */
    private function printResult(OutputInterface $output, JobsCollection $localJobs, array $remoteJobsIds, array $remoteJobsToRemove): void
    {
        $tableData = $this->buildTableData($localJobs);
        foreach ($tableData as $workflowFilename => $workflow) {
            foreach ($workflow as $workflowName => $jobs) {
                foreach ($jobs as $jobName => $combinations) {
                    $table = new Table($output);
                    $table->setHeaderTitle(sprintf('%s > %s > %s', $workflowFilename, $workflowName, $jobName));
                    $firstCombination = current($combinations);
                    if (false === is_array($firstCombination)) {
                        throw new \RuntimeException('The first combination is not an array. Impossible to get the header columns.');
                    }
                    $table->setHeaders(array_keys($firstCombination));
                    foreach ($combinations as $combination) {
                        $table->addRow($combination);
                    }
                    $table->render();
                    $output->writeln('');
                }
            }
        }

        if ([] !== $remoteJobsIds) {
            $table = new Table($output);
            $table->setHeaderTitle('Required Checks on GitHub');
            $table->setHeaders(
                [
                    'Status check',
                    'Action',
                ]
            );
            foreach ($remoteJobsIds as $remoteJobId) {
                $table->addRow(
                    [
                        $remoteJobId,
                        in_array($remoteJobId, $remoteJobsToRemove)
                            ? sprintf('<fg=red>%s %s</>', self::CROSS, Combination::ACTION_REMOVE)
                            : sprintf('<fg=green>%s %s</>', self::THICK, Combination::ACTION_NOTHING),
                    ]
                );
            }
            $table->render();
            $output->writeln('');
        }
    }

    /**
     * @return array<string, array<string, array<string, array<int, array<string, mixed>>>>> table data structured by workflow filename, workflow name, job, and their corresponding data
     */
    private function buildTableData(JobsCollection $localJobs): array
    {
        $tableData = [];
        foreach ($localJobs->getJobs() as $job) {
            foreach ($job->getMatrix()->getCombinations() as $combination) {
                $data = array_merge(
                    [
                        'Status Check' => (string) $combination,
                        'Action'       => match ($combination->getAction()) {
                            Combination::ACTION_NOTHING => sprintf('<fg=green>%s %s</>', self::THICK, Combination::ACTION_NOTHING),
                            Combination::ACTION_SYNC    => sprintf('<fg=yellow>%s %s</>', self::SYNC, Combination::ACTION_SYNC),
                            Combination::ACTION_REMOVE  => sprintf('<fg=red>%s %s</>', self::CROSS, Combination::ACTION_REMOVE),
                            default                     => throw new \RuntimeException(sprintf('Unknown action "%s"', $combination->getAction())),
                        },
                    ],
                    $combination->getCombination(),
                );

                $tableData[$combination->getWorkflowFilename()][$combination->getWorkflowName()][$combination->getJob()][] = $data;
            }
        }

        return $tableData;
    }
}
