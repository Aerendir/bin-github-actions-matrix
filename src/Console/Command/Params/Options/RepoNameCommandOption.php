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

use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class RepoNameCommandOption
{
    final public const string NAME     = 'repo';
    final public const string SHORTCUT = 'r';
    private const int MAX_ATTEMPTS     = 2;

    public function getValueOrAsk(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper, ?int $maxAttempts = null): string
    {
        $repoName = $this->getValueOrNull($input);

        return null === $repoName
            ? $this->askForValue($input, $output, $questionHelper, $maxAttempts)
            : $repoName;
    }

    public function getValueOrNull(InputInterface $input): ?string
    {
        $value = $input->getOption(self::NAME);

        if (null === $value) {
            return null;
        }

        return $this->validate($value);
    }

    private function askForValue(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper, ?int $maxAttempts = null): string
    {
        $question = new Question('Please, provide the name of the repo: ');
        $question->setHidden(false);
        $question->setMaxAttempts($maxAttempts ?? self::MAX_ATTEMPTS);
        $question->setValidator($this->validate(...));

        try {
            $repoName = $questionHelper->ask($input, $output, $question);
        } catch (ExceptionInterface $exception) {
            throw new MissingInputException('You must pass a valid name of the repo.', previous: $exception);
        }

        if (false === is_string($repoName)) {
            throw new MissingInputException('You must pass a valid name of the repo.');
        }

        return $repoName;
    }

    private function validate(mixed $repoName): string
    {
        if (false === is_string($repoName) || '' === trim($repoName)) {
            throw new InvalidOptionException('The repo name cannot be empty.');
        }

        return trim($repoName);
    }
}
