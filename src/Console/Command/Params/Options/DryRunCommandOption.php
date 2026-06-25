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

use Symfony\Component\Console\Input\InputInterface;

/**
 * Show what would change without touching the branch protection (read-only preview).
 *
 * A boolean (`VALUE_NONE`) flag: it is never prompted for, so only `isEnabled()` is needed.
 */
final class DryRunCommandOption
{
    public const string NAME = 'dry-run';

    public function isEnabled(InputInterface $input): bool
    {
        return true === $input->getOption(self::NAME);
    }
}
