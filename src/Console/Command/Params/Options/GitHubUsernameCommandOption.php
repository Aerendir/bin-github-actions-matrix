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

class GitHubUsernameCommandOption
{
    final public const string NAME     = 'username';
    final public const string SHORTCUT = 'u';
    private const int MAX_ATTEMPTS     = 2;

    public function getValueOrAsk(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper, ?int $maxAttempts = null): string
    {
        $username = $this->getValueOrNull($input);

        return null === $username
            ? $this->askForValue($input, $output, $questionHelper, $maxAttempts)
            : $username;
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
        $question = new Question('Please, provide your GitHub username: ');
        $question->setHidden(false);
        $question->setMaxAttempts($maxAttempts ?? self::MAX_ATTEMPTS);
        $question->setValidator($this->validate(...));

        try {
            $username = $questionHelper->ask($input, $output, $question);
        } catch (ExceptionInterface $exception) {
            throw new MissingInputException('You must pass a valid username of the repo.', previous: $exception);
        }

        // Re-validate the answer: besides being defensive, it narrows the QuestionHelper's `mixed`
        // return type down to `string` for the static analysers.
        return $this->validate($username);
    }

    private function validate(mixed $username): string
    {
        if (false === is_string($username) || '' === trim($username)) {
            throw new InvalidOptionException('The username cannot be empty.');
        }

        return trim($username);
    }
}
