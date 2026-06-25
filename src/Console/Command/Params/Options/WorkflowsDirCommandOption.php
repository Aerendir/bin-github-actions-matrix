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
 * The folder that directly contains the workflow `*.yml` files.
 *
 * Escape hatch for non-standard layouts. Resolved via config/inference (no interactive prompt),
 * so only `getValueOrNull()` is needed.
 */
final class WorkflowsDirCommandOption
{
    public const string NAME     = 'workflows-dir';
    public const string SHORTCUT = 'w';

    public function getValueOrNull(InputInterface $input): ?string
    {
        $value = $input->getOption(self::NAME);

        return is_string($value) ? $value : null;
    }
}
