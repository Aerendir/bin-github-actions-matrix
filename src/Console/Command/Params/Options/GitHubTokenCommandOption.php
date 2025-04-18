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
use Symfony\Component\Console\Question\Question;

class GitHubTokenCommandOption
{
    final public const string OPT_REPO_TOKEN          = 'token';
    final public const string OPT_REPO_TOKEN_SHORTCUT = 't';
    private const int MAX_ATTEMPTS                    = 2;

    public function getValueOrAsk(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper, int $maxAttempts = null): string
    {
        $token = $this->getValueOrNull($input);

        return null === $token
            ? $this->askForValue($input, $output, $questionHelper, $maxAttempts)
            : $token;
    }

    public function getValueOrNull(InputInterface $input): ?string
    {
        return $input->getOption(self::OPT_REPO_TOKEN);
    }

    private function askForValue(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper, int $maxAttempts = null): string
    {
        $question = new Question('Please, provide your GitHub token: ');
        $question->setHidden(false);
        $question->setMaxAttempts($maxAttempts ?? self::MAX_ATTEMPTS);

        try {
            $token = $questionHelper->ask($input, $output, $question);
        } catch (MissingInputException $missingInputException) {
            throw new MissingInputException('You must pass a valid token of the repo.', previous: $missingInputException);
        }

        return $token;
    }
}
