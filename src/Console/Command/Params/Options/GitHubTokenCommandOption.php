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

use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

use function Safe\preg_match;

class GitHubTokenCommandOption
{
    final public const string NAME     = 'token';
    final public const string SHORTCUT = 't';
    private const int MAX_ATTEMPTS     = 2;

    public function getValueOrAsk(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper, ?int $maxAttempts = null): string
    {
        $validationCallback = $this->getValidationCallback();
        $token              = $this->getValueOrNull($input);

        return null === $token
            ? $this->askForValue($input, $output, $questionHelper, $maxAttempts)
            : $validationCallback($token);
    }

    public function getValueOrNull(InputInterface $input): ?string
    {
        $validationCallback = $this->getValidationCallback();
        $value              = $input->getOption(self::NAME);

        if (null === $value) {
            return null;
        }

        return $validationCallback($value);
    }

    private function askForValue(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper, ?int $maxAttempts = null): string
    {
        $validationCallback = $this->getValidationCallback();
        $question           = new Question('Please, provide your GitHub token: ');
        $question->setHidden(false);
        $question->setMaxAttempts($maxAttempts ?? self::MAX_ATTEMPTS);
        $question->setValidator($validationCallback);

        try {
            $token = $questionHelper->ask($input, $output, $question);
        } catch (MissingInputException $missingInputException) {
            throw new MissingInputException('You must pass a valid token of the repo.', previous: $missingInputException);
        }

        return $token;
    }

    private function getValidationCallback(): callable
    {
        return static function (mixed $gitHubToken): string {
            if (0 === preg_match('/^ghp_[A-Za-z0-9]{36}$/', $gitHubToken)) {
                throw new InvalidOptionException('The GitHub Token format is invalid.');
            }

            return $gitHubToken;
        };
    }
}
