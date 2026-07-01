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

class GitHubTokenCommandOption
{
    final public const string NAME     = 'token';
    final public const string SHORTCUT = 't';
    private const int MAX_ATTEMPTS     = 2;

    /**
     * The accepted GitHub token formats, as the single source of truth shared by every token source
     * (CLI option, token file, environment variable), so the pattern is never duplicated:
     *  - classic personal access token:      `ghp_` + 36 alphanumerics
     *  - fine-grained personal access token: `github_pat_` + 82 chars (alphanumerics and underscores)
     *  - app / installation access token:    `ghs_` + 36 alphanumerics.
     */
    private const string FORMAT_PATTERN = '/^(?:ghp_[A-Za-z0-9]{36}|github_pat_\w{82}|ghs_[A-Za-z0-9]{36})$/';

    public function getValueOrAsk(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper, ?int $maxAttempts = null): string
    {
        $token = $this->getValueOrNull($input);

        return null === $token
            ? $this->askForValue($input, $output, $questionHelper, $maxAttempts)
            : $token;
    }

    public function getValueOrNull(InputInterface $input): ?string
    {
        $value = $input->getOption(self::NAME);

        if (null === $value) {
            return null;
        }

        return $this->validate($value);
    }

    /**
     * Tells whether the given token matches one of the accepted GitHub token formats.
     *
     * Exposed so other token sources (e.g. a token read from a file or an environment variable) can
     * validate against the very same definition without duplicating the pattern.
     */
    public function isValidFormat(string $token): bool
    {
        $result = preg_match(self::FORMAT_PATTERN, $token);
        if (false === $result) {
            throw new \RuntimeException('Unable to validate the GitHub token format.');
        }

        return 1 === $result;
    }

    private function askForValue(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper, ?int $maxAttempts = null): string
    {
        $question = new Question('Please, provide your GitHub token: ');
        $question->setHidden(false);
        $question->setMaxAttempts($maxAttempts ?? self::MAX_ATTEMPTS);
        $question->setValidator($this->validate(...));

        try {
            $token = $questionHelper->ask($input, $output, $question);
        } catch (ExceptionInterface $exception) {
            throw new MissingInputException('You must pass a valid token of the repo.', previous: $exception);
        }

        // Re-validate the answer: besides being defensive, it narrows the QuestionHelper's `mixed`
        // return type down to `string` for the static analysers.
        return $this->validate($token);
    }

    private function validate(mixed $gitHubToken): string
    {
        if (false === is_string($gitHubToken) || false === $this->isValidFormat($gitHubToken)) {
            throw new InvalidOptionException('The GitHub Token format is invalid.');
        }

        return $gitHubToken;
    }
}
