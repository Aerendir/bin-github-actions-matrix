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

namespace Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options;

use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class RepoBranchCommandOption
{
    final public const string NAME     = 'branch';
    final public const string SHORTCUT = 'b';
    private const int MAX_ATTEMPTS     = 2;

    /**
     * @param array<array-key, string> $protectedBranches
     */
    public function getValueOrAsk(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper, array $protectedBranches, ?int $maxAttempts = null): string
    {
        $branch = $this->getValueOrNull($input);
        if (null !== $branch) {
            return $branch;
        }

        if (1 === count($protectedBranches)) {
            return $protectedBranches[0];
        }

        return null === $branch
            ? $this->askForValue($input, $output, $questionHelper, $protectedBranches, $maxAttempts)
            : $branch;
    }

    public function getValueOrNull(InputInterface $input): ?string
    {
        return $input->getOption(self::NAME);
    }

    /**
     * @param array<array-key, string> $protectedBranches
     */
    private function askForValue(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper, array $protectedBranches, ?int $maxAttempts = null): string
    {
        $question = new ChoiceQuestion('Please, provide your GitHub branch: ', $protectedBranches);
        $question->setMaxAttempts($maxAttempts ?? self::MAX_ATTEMPTS);

        try {
            $branch = $questionHelper->ask($input, $output, $question);
        } catch (MissingInputException $missingInputException) {
            throw new MissingInputException('You must pass a valid branch of the repo.', previous: $missingInputException);
        }

        return $branch;
    }
}
