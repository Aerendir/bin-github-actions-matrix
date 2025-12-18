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

This tool provides two commands to manage GitHub branch protection rules for your repository's workflows.

### Available Commands

#### `compare` - Compare workflows with protection rules

Compare the workflows configured in your repository with the current matrix of protection rules on GitHub.

```bash
vendor/bin/github-actions-matrix compare [options]
```

**Options:**
- `-u, --username=USERNAME` - Your GitHub username
- `-b, --branch=BRANCH` - The branch to compare
- `-t, --token=TOKEN` - Your GitHub access token

**Example:**
```bash
vendor/bin/github-actions-matrix compare --username=myuser --branch=main
```

This command will display a comparison table showing:
- Local workflows and their job matrices
- Current protection rules on GitHub
- Actions needed (sync, remove, or nothing)

#### `sync` - Sync workflows with protection rules

Synchronize workflows configured in the repository with the current matrix of protection rules on GitHub.

```bash
vendor/bin/github-actions-matrix sync [options]
```

**Options:**
- `-u, --username=USERNAME` - Your GitHub username
- `-b, --branch=BRANCH` - The branch to sync
- `-t, --token=TOKEN` - Your GitHub access token

**Example:**
```bash
vendor/bin/github-actions-matrix sync --username=myuser --branch=main
```

This command will:
1. Remove obsolete protection rules
2. Add new protection rules based on your workflow matrices

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

#### Priority Order

The commands use the following priority order to determine values:

1. **CLI options** (highest priority) - `--username`, `--branch`
2. **Configuration file** - values from `gh-actions-matrix.php`
3. **Git configuration** - for username only, read from git config
4. **Auto-selection** - for branch only, if there's only one protected branch
5. **Interactive prompt** (lowest priority) - asks for missing values

#### Benefits

- **No repeated prompts**: Once configured, commands won't ask for user/branch
- **Flexible**: Command-line options still override config file values
- **Project-specific**: Each project can have its own configuration
- **Secure**: Keep sensitive config out of version control

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
