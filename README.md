<p align="center">
    <a href="http://www.serendipityhq.com" target="_blank">
        <img style="max-width: 350px" src="http://www.serendipityhq.com/assets/open-source-projects/Logo-SerendipityHQ-Icon-Text-Purple.png">
    </a>
</p>

<h1 align="center">GitHub Actions Matrix</h1>
<p align="center">A CLI tool to sync configured workflows with branch protection rules.</p>
<p align="center">
    <a href="https://github.com/Aerendir/bin-github-actions-matrix/releases"><img src="https://img.shields.io/packagist/v/aerendir/bin-github-actions-matrix.svg?style=flat-square"></a>
    <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square"></a>
    <a href="https://github.com/Aerendir/bin-github-actions-matrix/releases"><img src="https://img.shields.io/packagist/php-v/aerendir/bin-github-actions-matrix?color=%238892BF&style=flat-square&logo=php" /></a>
</p>
<p>
    Supports:
    <a title="Supports Symfony ^7.4" href="https://github.com/Aerendir/bin-github-actions-matrix/actions?query=branch%3Amaster"><img title="Supports Symfony ^7.4" src="https://img.shields.io/badge/Symfony-%5E7.4-333?style=flat-square&logo=symfony" /></a>
    <a title="Supports Symfony ^8.0" href="https://github.com/Aerendir/bin-github-actions-matrix/actions?query=branch%3Amaster"><img title="Supports Symfony ^8.0" src="https://img.shields.io/badge/Symfony-%5E8.0-333?style=flat-square&logo=symfony" /></a>
</p>
<p>
    Tested with:
    <a title="Supports Symfony ^7.4" href="https://github.com/Aerendir/bin-github-actions-matrix/actions?query=branch%3Amaster"><img title="Supports Symfony ^7.4" src="https://img.shields.io/badge/Symfony-%5E7.4-333?style=flat-square&logo=symfony" /></a>
    <a title="Supports Symfony ^8.0" href="https://github.com/Aerendir/bin-github-actions-matrix/actions?query=branch%3Amaster"><img title="Supports Symfony ^8.0" src="https://img.shields.io/badge/Symfony-%5E8.0-333?style=flat-square&logo=symfony" /></a>
</p>

## Current Status

[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=Aerendir_bin-github-actions-matrix&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=Aerendir_bin-github-actions-matrix)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=Aerendir_bin-github-actions-matrix&metric=alert_status)](https://sonarcloud.io/dashboard?id=Aerendir_bin-github-actions-matrix)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=Aerendir_bin-github-actions-matrix&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=Aerendir_bin-github-actions-matrix)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=Aerendir_bin-github-actions-matrix&metric=security_rating)](https://sonarcloud.io/dashboard?id=Aerendir_bin-github-actions-matrix)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=Aerendir_bin-github-actions-matrix&metric=sqale_index)](https://sonarcloud.io/dashboard?id=Aerendir_bin-github-actions-matrix)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=Aerendir_bin-github-actions-matrix&metric=vulnerabilities)](https://sonarcloud.io/dashboard?id=Aerendir_bin-github-actions-matrix)

[![PHPStan](https://github.com/Aerendir/bin-github-actions-matrix/workflows/PHPStan/badge.svg)](https://github.com/Aerendir/bin-github-actions-matrix/actions?query=branch%3Adev)
[![PSalm](https://github.com/Aerendir/bin-github-actions-matrix/workflows/PSalm/badge.svg)](https://github.com/Aerendir/bin-github-actions-matrix/actions?query=branch%3Adev)
[![PHPUnit](https://github.com/Aerendir/bin-github-actions-matrix/workflows/PHPunit/badge.svg)](https://github.com/Aerendir/bin-github-actions-matrix/actions?query=branch%3Adev)
[![Composer](https://github.com/Aerendir/bin-github-actions-matrix/workflows/Composer/badge.svg)](https://github.com/Aerendir/bin-github-actions-matrix/actions?query=branch%3Adev)
[![PHP CS Fixer](https://github.com/Aerendir/bin-github-actions-matrix/workflows/PHP%20CS%20Fixer/badge.svg)](https://github.com/Aerendir/bin-github-actions-matrix/actions?query=branch%3Adev)
[![Rector](https://github.com/Aerendir/bin-github-actions-matrix/workflows/Rector/badge.svg)](https://github.com/Aerendir/bin-github-actions-matrix/actions?query=branch%3Adev)

[![codecov](https://codecov.io/gh/Aerendir/bin-github-actions-matrix/branch/dev/graph/badge.svg?token=iZiIGuk91g)](https://codecov.io/gh/Aerendir/bin-github-actions-matrix)


[![CodeCov SunBurst](https://codecov.io/gh/Aerendir/bin-github-actions-matrix/branch/dev/graphs/sunburst.svg?token=iZiIGuk91g)](https://codecov.io/gh/Aerendir/bin-github-actions-matrix)
[![CodeCov Tree](https://codecov.io/gh/Aerendir/bin-github-actions-matrix/branch/dev/graphs/tree.svg?token=iZiIGuk91g)](https://codecov.io/gh/Aerendir/bin-github-actions-matrix)
[![CodeCov I Cicle](https://codecov.io/gh/Aerendir/bin-github-actions-matrix/branch/dev/graphs/icicle.svg?token=iZiIGuk91g)](https://codecov.io/gh/Aerendir/bin-github-actions-matrix)

<hr />
<h3 align="center">
    <b>Do you like this library?</b><br />
    <b><a href="#js-repo-pjax-container">LEAVE A &#9733;</a></b>
</h3>
<p align="center">
    or run<br />
    <code>composer global require symfony/thanks && composer thanks</code><br />
    to say thank you to all libraries you use in your current project, this included!
</p>
<hr />

## Install Serendipity HQ Bin GitHub Actions Matrix

    $ composer require aerendir/bin-github-actions-matrix

This library follows the http://semver.org/ versioning conventions.

## Usage

This tool provides the `sync` command to manage GitHub branch protection rules for your repository's workflows.

### Available Commands

#### `sync` - Sync workflows with protection rules

Synchronize workflows configured in the repository with the current matrix of protection rules on GitHub.

```bash
vendor/bin/github-actions-matrix sync [options]
```

**Options:**
- `-u, --username=USERNAME` - Your GitHub username
- `-r, --repo=REPO` - The name of the GitHub repository
- `-b, --branch=BRANCH` - The branch to sync
- `-t, --token=TOKEN` - Your GitHub access token
- `-p, --project-dir=PROJECT-DIR` - The project root that contains the `.github/workflows` folder
- `-w, --workflows-dir=WORKFLOWS-DIR` - The folder that directly contains the workflow `*.yml`/`*.yaml` files (non-standard layouts)
- `-f, --force` - Apply the changes without asking for confirmation (e.g. in CI)
- `--dry-run` - Show what would change without touching the branch protection (read-only preview)
- `--check` - CI gate: read-only, exit `0` if aligned, `1` if drift, `2` on error (implies `--dry-run`)

**Example:**
```bash
vendor/bin/github-actions-matrix sync --username=myuser --branch=main
```

This command will:
1. Remove obsolete protection rules
2. Add new protection rules based on your workflow matrices

Before applying any change, `sync` shows the plan and asks for confirmation. Pass `-f, --force` to skip the prompt (for example in CI).

To **verify** alignment in CI without changing anything, use `sync --check`: it is read-only and encodes the result in the exit code — `0` if the branch protection matches the workflows, `1` if it drifts, `2` on error (bad token, network, parse). It needs a token with read access to the branch protection.

#### Matrix expansion (`include` / `exclude`)

The tool expands `strategy.matrix` into the same required-check contexts GitHub generates, honouring both `exclude` and `include` with GitHub's documented semantics (see GitHub's docs: [Using a matrix for your jobs](https://docs.github.com/en/actions/using-jobs/using-a-matrix-for-your-jobs), in particular [Excluding matrix configurations](https://docs.github.com/en/actions/using-jobs/using-a-matrix-for-your-jobs#excluding-matrix-configurations) and [Expanding or adding matrix configurations](https://docs.github.com/en/actions/using-jobs/using-a-matrix-for-your-jobs#expanding-or-adding-matrix-configurations)):

- **`exclude`** removes the matching combinations from the cartesian product.
- **`include`** is applied **after** `exclude`, processing its entries **in order**. An entry is merged into every base combination it does not conflict with — it may not overwrite an **original** matrix value, but it may add new keys or overwrite values added by an earlier `include` entry, and a single entry can extend many combinations. An entry that fits no base combination becomes a **new** combination. An `include`-only matrix (no other keys) produces exactly one combination per entry.

```yaml
strategy:
  matrix:
    fruit: [apple, pear]
    animal: [cat, dog]
    include:
      - color: green
      - color: pink
        animal: cat
      - fruit: apple
        shape: circle
      - fruit: banana
```

resolves to the contexts `job (apple, cat, pink, circle)`, `job (apple, dog, green, circle)`, `job (pear, cat, pink)`, `job (pear, dog, green)` and `job (banana)` — matching GitHub exactly.

#### Non-derivable contexts (interpolated names / dynamic matrices)

Some required-check context names **cannot be derived statically** from the YAML, so the tool must not guess them:

- an interpolated job **name**, e.g. `name: build ${{ matrix.os }}` — GitHub shows the resolved value, not `job (values)`;
- a **dynamic matrix**, e.g. `matrix: ${{ fromJson(needs.setup.outputs.matrix) }}` or matrix keys/values built from expressions — the values do not exist as literal arrays in the file (and may even be branch-dependent).

When such a job is detected, the tool **does not compute a (wrong) context** for it and prints a clear **warning** before any change. Because it cannot know the real context name, `sync` would otherwise risk removing it, so you have two clean options:

1. **Declare the real context name** so `sync` preserves it (it is treated like an external required check and never removed):
   ```php
   $config->addRequiredCheck('build ubuntu-latest');
   $config->addRequiredCheck('build windows-latest');
   ```
2. **Use a static "gate" job** (recommended for dynamic matrices): a single job with a static name that `needs:` the dynamic matrix job, and make *that* gate job the required check. The tool derives the gate job's context normally, and it stays green only when the whole matrix passes.

### Configuration File

To avoid repeatedly providing the same options, you can create a configuration file `gh-actions-matrix.php` in your project root.

#### Setup

1. Copy the example configuration file:
   ```bash
   cp gh-actions-matrix.dist.php gh-actions-matrix.php
   ```

2. Edit `gh-actions-matrix.php` to set your default values:
   ```php
   <?php
   
   $config = new Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig();
   
   // Set the default GitHub username for the repository
   $config->setUser('your-github-username');
   
   // Set the default branch to sync/compare
   $config->setBranch('main');
   
   return $config;
   ```

3. Add `gh-actions-matrix.php` to your `.gitignore` file to keep local configurations private.

#### Configuration Options Reference

Every value can be provided on the command line or declared in the configuration file. The configuration methods and their CLI equivalents:

| Config method | CLI option | Description |
|---|---|---|
| `setUser(string)` | `-u, --username` | GitHub username / repository owner. |
| `setRepoName(string)` | `-r, --repo` | Repository name. Needed when git is not available to infer it (e.g. inside a container). |
| `setBranch(string)` | `-b, --branch` | The protected branch to compare/sync. |
| `setTokenFile(string)` | _(see `-t, --token`)_ | Name/path of a file containing the GitHub token, resolved against `projectDir → git root → cwd`. The CLI `-t, --token` instead passes the token value directly. |
| `setProjectDir(string)` | `-p, --project-dir` | Project root that contains `.github/workflows`; also the preferred base directory for the token file. |
| `setWorkflowsDir(string)` | `-w, --workflows-dir` | Folder that directly contains the workflow `*.yml`/`*.yaml` files. Escape hatch for non-standard layouts. |

> The GitHub token must have **repo-admin** scope. Classic (`ghp_…`), fine-grained (`github_pat_…`) and app/installation (`ghs_…`) tokens are accepted. Provide it via `-t, --token`, the `GH_MATRIX_TOKEN` environment variable (recommended in CI — it keeps the secret off disk and out of the process arguments), or `setTokenFile()` (pointing at a gitignored file) — never commit it. Resolution order: `--token` → `GH_MATRIX_TOKEN` (env) → token file → interactive prompt.

#### Priority Order

The commands use the following priority order to determine values:

1. **CLI options** (highest priority) - `--username`, `--repo`, `--branch`, `--project-dir`, `--workflows-dir`
2. **Configuration file** - values from `gh-actions-matrix.php`
3. **Git configuration** - for username and repo-name, read from git config/remote; for the workflows location, the git root
4. **Inference / auto-selection** - for branch, if there's only one protected branch; for the workflows location, the tool's own `__DIR__` fallbacks
5. **Interactive prompt** (lowest priority) - asks for missing values

#### Workflows Location Resolution

By default the tool discovers the `.github/workflows` folder by inference (git root, then its own `__DIR__`). In a monorepo, or a `type: path` install where the tool runs from a sub-project (e.g. `backend/`) and cannot infer the location, declare it explicitly. The first **existing** folder in this chain wins:

1. `--workflows-dir` (CLI)
2. `setWorkflowsDir()` (config)
3. `--project-dir` (CLI) → `<project-dir>/.github/workflows`
4. `setProjectDir()` (config) → `<projectDir>/.github/workflows`
5. The **git root** (`git rev-parse --show-toplevel`) → `<root>/.github/workflows`
6. The tool's own `__DIR__` fallbacks (the historical behaviour)

`setProjectDir()` is the primary, intuitive concept (the project root that contains `.github/workflows`); `setWorkflowsDir()` is the escape hatch for a non-standard layout. With nothing declared, behaviour is identical to before.

#### Token File Resolution

The `setTokenFile()` path is resolved against the first available base directory in this chain:

1. The **configured project dir** (`setProjectDir()`) — when set.
2. The **git root** (`git rev-parse --show-toplevel`) — when git is available.
3. The **current working directory** — fallback for containerised or non-git environments.

#### Monorepo / Containerized Setup

In a monorepo the workflows usually live at the **repository root** (`/.github/workflows`), while the tool is installed and run from a **sub-project** (e.g. `backend/`) — often inside a container that mounts only that sub-project and has neither the repository's `.git` nor the root `.github` reachable by inference. Declare the location explicitly instead.

Because `gh-actions-matrix.php` is **committed to the repository**, it must be **host-agnostic**: never hardcode machine-specific absolute paths. Use `setProjectDir('.')` so everything resolves relative to the directory the tool runs from (its current working directory), and mount the root `.github` under that directory in your container (read-only is enough).

```php
<?php

// Committed config — keep it HOST-AGNOSTIC (no machine-specific absolute paths).
$config = new Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig();

$config->setUser('your-org');
$config->setRepoName('your-repo');   // git remote isn't available inside the container
$config->setBranch('master');

// The project root is the current working directory (where the tool runs, e.g. backend/).
// From it the tool derives "./.github/workflows" (workflows location) and the token-file base — no host paths.
$config->setProjectDir('.');

// Token read from "<cwd>/gh_token" (git root isn't available in the container).
$config->setTokenFile('gh_token');

return $config;
```

With this config, run from the sub-project, the tool finds `./.github/workflows` and reads the token from `./gh_token`, regardless of the host machine.

#### Benefits

- **No repeated prompts**: Once configured, commands won't ask for user/branch
- **Flexible**: Command-line options still override config file values
- **Project-specific**: Each project can have its own configuration
- **Secure**: Keep sensitive config out of version control

### Optional Combinations

Sometimes you want to test your code with a new version of PHP or a dependency to know if it's already compatible, but you don't want the entire workflow to fail if the tests don't pass. This is where "optional combinations" come in.

An optional combination is a matrix combination that is **not marked as required** in your branch protection rules. This means:
- The job will still run in your GitHub Actions workflow
- If it fails, it won't block merging or mark the overall workflow as failed
- It appears as an optional check in pull requests

#### Configuring Optional Combinations

Use the `markOptionalCombination()` method in your `gh-actions-matrix.php` configuration file:

```php
<?php

$config = new Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig();

// Set your default configuration
$config->setUser('your-github-username');
$config->setBranch('main');

// Mark specific combinations as optional (not required)
// First argument: workflow name (as defined in your workflow file)
// Second argument: combination to mark as optional

// Example 1: Test PHP 8.4 without making it required
$config->markOptionalCombination('phpunit', ['php' => '8.4']);

// Example 2: Test a specific combination of PHP and Symfony
$config->markOptionalCombination('phpunit', ['php' => '8.3', 'symfony' => '~8.0']);

// You can mark multiple combinations for the same workflow
$config->markOptionalCombination('integration-tests', ['php' => '8.4', 'database' => 'postgresql']);

return $config;
```

#### How It Works

When you specify an optional combination:

1. **The combination must exist** in your workflow matrix. If it doesn't exist or is explicitly excluded, you'll get an error.
2. **The job still runs** in your GitHub Actions workflow as normal.
3. **It's not added to required status checks** when you run the `sync` command.
4. **Pull requests can be merged** even if the optional combination fails.

#### Partial Matching

Optional combinations support partial matching. For example:

```php
// This marks ALL combinations with PHP 8.4 as optional, regardless of other matrix values
$config->markOptionalCombination('phpunit', ['php' => '8.4']);

// If your matrix is:
// matrix:
//   php: ['8.3', '8.4']
//   symfony: ['~6.4', '~7.4']
//
// Then these combinations will be marked as optional:
// - phpunit (8.4, ~6.4)
// - phpunit (8.4, ~7.4)
```

#### Use Cases

- **Testing new PHP versions** before they're officially supported
- **Experimental dependency versions** (e.g., testing Symfony 8.0 before stable release)
- **Optional database engines** that you want to test but not require
- **Performance tests** that might be flaky but provide useful information
- **Nightly builds** or bleeding-edge combinations

#### Examples

**Without config file:**
```bash
$ vendor/bin/github-actions-matrix sync
# Prompts for username
# Prompts for token
# Prompts for branch (if multiple protected branches)
```

**With config file:**
```bash
$ vendor/bin/github-actions-matrix sync
# Only prompts for token (username and branch taken from config)
```

**Overriding config file:**
```bash
$ vendor/bin/github-actions-matrix sync --username different-user --branch dev
# Uses 'different-user' and 'dev' instead of config values
```

### Non-matrix jobs

A job **without** a `strategy.matrix` (for example a single `build` or `deploy` job) maps to **one** required status check whose context is the **bare job name** (e.g. `build`), matching how GitHub names it. Such jobs no longer cause an error.

### Ignoring jobs

Some jobs run in CI but should never gate pull requests (a deploy job, a nightly task, …). Exclude them from the computed set with `ignoreJob()` in `gh-actions-matrix.php`:

```php
$config->ignoreJob('deploy');
```

An ignored job is **never added** to the branch protection and, if it is currently required, it is **removed** by `sync`.

### External / non-workflow required checks

Some required checks (codecov, kodiak, GitGuardian, …) come from external apps and live in **no** workflow file, so the tool cannot derive them. Declare them with `addRequiredCheck()` so `sync` treats them as part of the desired set:

```php
$config->addRequiredCheck('codecov');
```

A declared check is **never removed** by `sync` (and is added if it is not yet required). This is what lets `sync` remove a stale matrix context for real, without ever deleting a check it simply cannot read from the workflows.

<hr />
<h3 align="center">
    <b>Do you like this library?</b><br />
    <b><a href="#js-repo-pjax-container">LEAVE A &#9733;</a></b>
</h3>
<p align="center">
    or run<br />
    <code>composer global require symfony/thanks && composer thanks</code><br />
    to say thank you to all libraries you use in your current project, this included!
</p>
<hr />
