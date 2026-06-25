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

$config = new Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig();

// Set the default GitHub username for the repository
// $config->setUser('your-github-username');

// Set the default branch to sync/compare
// $config->setBranch('main');

// Set the name of the repository
// $config->setRepoName('your-repo-name');

// Set the name of the file that contains the GitHub token.
// The file is resolved relative to: the configured project dir (see setProjectDir() below, when set),
// then the git root (when available), then the current working directory.
// $config->setTokenFile('gh_token');

// Set the project root that contains the ".github/workflows" folder.
// Useful in a monorepo or a "type: path" install where the tool runs from a sub-project (e.g. backend/)
// and cannot infer the location from git or its own __DIR__. It is also the preferred base for the token file.
// $config->setProjectDir('/srv/app');

// Set the folder that directly contains the workflow "*.yml" files.
// Escape hatch for non-standard layouts where the workflows do NOT live under "<projectDir>/.github/workflows".
// $config->setWorkflowsDir('/srv/app/.github/workflows');

return $config;
