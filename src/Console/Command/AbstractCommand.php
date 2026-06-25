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

namespace Aerendir\Bin\GitHubActionsMatrix\Console\Command;

use Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\GitHubTokenCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\GitHubUsernameCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\ProjectDirCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\RepoBranchCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\RepoNameCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\WorkflowsDirCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Repo\Reader as RepoReader;
use Aerendir\Bin\GitHubActionsMatrix\ValueObject\JobsCollection;
use Aerendir\Bin\GitHubActionsMatrix\Workflow\Comparator;
use Aerendir\Bin\GitHubActionsMatrix\Workflow\Reader as WorkflowsReader;
use Github\Api\Repo;
use Github\Api\Repository\Protection;
use Github\AuthMethod;
use Github\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttplugClient;

use function Safe\file_get_contents;
use function Safe\getcwd;
use function Safe\realpath;

abstract class AbstractCommand extends Command
{
    protected readonly string $branchName;
    private readonly GHMatrixConfig $config;
    private readonly GitHubUsernameCommandOption $gitHubUsernameCommandOption;
    private readonly GitHubTokenCommandOption $gitHubTokenCommandOption;
    private readonly RepoBranchCommandOption $repoBranchCommandOption;
    private readonly RepoNameCommandOption $repoNameCommandOption;
    private readonly ProjectDirCommandOption $projectDirCommandOption;
    private readonly WorkflowsDirCommandOption $workflowsDirCommandOption;
    private readonly Client $githubClient;
    private readonly RepoReader $repoReader;
    private readonly WorkflowsReader $workflowsReader;
    private readonly Comparator $comparator;
    private string $repoUsername;
    private string $repoName;
    private JobsCollection $localJobs;
    private array $remoteJobsIds;
    private Protection $protection;
    private array $combinationsToRemove;

    public function __construct(
        ?GHMatrixConfig $config = null,
        ?GitHubUsernameCommandOption $gitHubUsernameCommandOption = null,
        ?GitHubTokenCommandOption $gitHubTokenCommandOption = null,
        ?RepoBranchCommandOption $repoBranchCommandOption = null,
        ?RepoNameCommandOption $repoNameCommandOption = null,
        ?RepoReader $repoReader = null,
        ?WorkflowsReader $workflowsReader = null,
        ?Comparator $comparator = null,
        ?Client $githubClient = null,
        ?ProjectDirCommandOption $projectDirCommandOption = null,
        ?WorkflowsDirCommandOption $workflowsDirCommandOption = null,
    ) {
        parent::__construct();
        $this->config                      = $config                      ?? new GHMatrixConfig();
        $this->gitHubUsernameCommandOption = $gitHubUsernameCommandOption ?? new GitHubUsernameCommandOption();
        $this->gitHubTokenCommandOption    = $gitHubTokenCommandOption    ?? new GitHubTokenCommandOption();
        $this->repoBranchCommandOption     = $repoBranchCommandOption     ?? new RepoBranchCommandOption();
        $this->repoNameCommandOption       = $repoNameCommandOption       ?? new RepoNameCommandOption();
        $this->projectDirCommandOption     = $projectDirCommandOption     ?? new ProjectDirCommandOption();
        $this->workflowsDirCommandOption   = $workflowsDirCommandOption   ?? new WorkflowsDirCommandOption();
        $this->repoReader                  = $repoReader                  ?? new RepoReader();
        $this->workflowsReader             = $workflowsReader             ?? new WorkflowsReader();
        $this->comparator                  = $comparator                  ?? new Comparator($this->config);
        $this->githubClient                = $githubClient                ?? Client::createWithHttpClient(new HttplugClient());
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption(GitHubUsernameCommandOption::NAME, GitHubUsernameCommandOption::SHORTCUT, InputOption::VALUE_REQUIRED, 'Your GitHub username.');
        $this->addOption(GitHubTokenCommandOption::NAME, GitHubTokenCommandOption::SHORTCUT, InputOption::VALUE_REQUIRED, 'Your GitHub access token.');
        $this->addOption(RepoBranchCommandOption::NAME, RepoBranchCommandOption::SHORTCUT, InputOption::VALUE_REQUIRED, 'The branch for which the matrix has to be synchronized.');
        $this->addOption(RepoNameCommandOption::NAME, RepoNameCommandOption::SHORTCUT, InputOption::VALUE_REQUIRED, 'The name of the GitHub repository.');
        $this->addOption(ProjectDirCommandOption::NAME, ProjectDirCommandOption::SHORTCUT, InputOption::VALUE_REQUIRED, 'The project root that contains the ".github/workflows" folder.');
        $this->addOption(WorkflowsDirCommandOption::NAME, WorkflowsDirCommandOption::SHORTCUT, InputOption::VALUE_REQUIRED, 'The folder that directly contains the workflow "*.yml"/"*.yaml" files (non-standard layouts).');
    }

    protected function init(InputInterface $input, OutputInterface $output): void
    {
        $questionHelper = $this->getHelper('question');

        if (false === $questionHelper instanceof QuestionHelper) {
            throw new \RuntimeException(sprintf('The helper %s is not available.', QuestionHelper::class));
        }

        // Cannot get the username from the git config. Pass it explicitly using the option "--username"'
        $repoUsername   = $this->getRepoUsername($input, $output, $questionHelper);
        $repoToken      = $this->getRepoToken($input, $output, $questionHelper);
        $this->repoName = $this->getRepoName($input, $output, $questionHelper);

        // The candidate folders are resolved at run time (CLI options --project-dir/--workflows-dir, config,
        // git root) and passed to read(): the reader itself is a plain injected collaborator.
        $workflowCandidates = $this->resolveWorkflowCandidates($input);
        $this->localJobs    = $this->workflowsReader->read($workflowCandidates);

        $this->githubClient->authenticate(tokenOrLogin: $repoToken, authMethod: AuthMethod::ACCESS_TOKEN);
        $repo = $this->githubClient->api('repo');
        if (false === $repo instanceof Repo) {
            throw new \RuntimeException('The API returned an unexpected object');
        }

        $allBranches       = $repo->branches($repoUsername, $this->repoName);
        $protectedBranches = $this->repoReader->filterProtectedBranches($allBranches);
        $this->branchName  = $this->getBranchName($input, $output, $questionHelper, $protectedBranches);

        $this->protection = $repo->protection();
        $protectionRules  = $this->protection->show($repoUsername, $this->repoName, $this->branchName);

        $requiredStatusChecks = $protectionRules['required_status_checks'];
        $this->remoteJobsIds  = $requiredStatusChecks['contexts'];

        $this->combinationsToRemove = $this->comparator->compare($this->localJobs, $this->remoteJobsIds);
    }

    protected function getRepoUsername(?InputInterface $input = null, ?OutputInterface $output = null, ?QuestionHelper $questionHelper = null): string
    {
        if (isset($this->repoUsername)) {
            return $this->repoUsername;
        }

        // Priority 1: CLI option (if input is provided)
        if (null !== $input) {
            $cliUsername = $this->gitHubUsernameCommandOption->getValueOrNull($input);
            if (null !== $cliUsername) {
                return $this->repoUsername = $cliUsername;
            }
        }

        // Priority 2: Config file
        $configUsername = $this->config->getUser();
        if (null !== $configUsername) {
            return $this->repoUsername = $configUsername;
        }

        // Priority 3: Git repository config
        $repoUsername = $this->repoReader->getUsername();
        if (null !== $repoUsername) {
            return $this->repoUsername = $repoUsername;
        }

        // Priority 4: Ask the user
        if (in_array(null, [$input, $output, $questionHelper], true)) {
            throw new \RuntimeException('You must pass the input and output objects and the question helper to get the username.');
        }

        return $this->repoUsername = $this->gitHubUsernameCommandOption->getValueOrAsk($input, $output, $questionHelper);
    }

    protected function getRepoToken(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper): string
    {
        // Priority 1: CLI option
        $cliToken = $this->gitHubTokenCommandOption->getValueOrNull($input);
        if (null !== $cliToken) {
            return $cliToken;
        }

        // Priority 2: Token file from config
        $tokenFilePath = $this->config->getTokenFile();
        if (null !== $tokenFilePath) {
            $token = $this->readTokenFromFile($tokenFilePath);
            if (null !== $token) {
                return $token;
            }
        }

        // Priority 3: Ask the user
        return $this->gitHubTokenCommandOption->getValueOrAsk($input, $output, $questionHelper);
    }

    /**
     * @param array<array-key, string> $protectedBranches
     */
    protected function getBranchName(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper, array $protectedBranches): string
    {
        // Priority 1: CLI option
        $cliBranch = $this->repoBranchCommandOption->getValueOrNull($input);
        if (null !== $cliBranch) {
            return $cliBranch;
        }

        // Priority 2: Config file
        $configBranch = $this->config->getBranch();
        if (null !== $configBranch) {
            // Validate that the configured branch exists in protected branches
            if (in_array($configBranch, $protectedBranches, true)) {
                return $configBranch;
            }
            // If configured branch is not in protected branches, warn and fall through
            $output->writeln(sprintf(
                '<comment>Warning: Configured branch "%s" is not in the list of protected branches. Falling back to selection.</comment>',
                $configBranch
            ));
        }

        // Priority 3: Auto-select if single branch or ask user
        // RepoBranchCommandOption handles both single branch auto-selection and prompting
        return $this->repoBranchCommandOption->getValueOrAsk($input, $output, $questionHelper, $protectedBranches);
    }

    protected function getRepoName(?InputInterface $input = null, ?OutputInterface $output = null, ?QuestionHelper $questionHelper = null): string
    {
        if (isset($this->repoName)) {
            return $this->repoName;
        }

        // Priority 1: CLI option (if input is provided)
        if (null !== $input) {
            $cliRepoName = $this->repoNameCommandOption->getValueOrNull($input);
            if (null !== $cliRepoName) {
                return $this->repoName = $cliRepoName;
            }
        }

        // Priority 2: Config file
        $configRepoName = $this->config->getRepoName();
        if (null !== $configRepoName) {
            return $this->repoName = $configRepoName;
        }

        // Priority 3: Git repository remote
        try {
            return $this->repoName = $this->repoReader->getRepoName();
        } catch (\Throwable) {
            // Intentionally ignored: git remote may not be available in containerised/monorepo environments.
            // Fall through to prompt the user for the repo name.
        }

        // Priority 4: Ask the user
        if (in_array(null, [$input, $output, $questionHelper], true)) {
            throw new \RuntimeException('You must pass input/output/helper to resolve the repo name.');
        }

        return $this->repoName = $this->repoNameCommandOption->getValueOrAsk($input, $output, $questionHelper);
    }

    protected function getLocalJobs(): JobsCollection
    {
        return $this->localJobs;
    }

    protected function getRemoteJobsIds(): array
    {
        return $this->remoteJobsIds;
    }

    protected function getProtection(): Protection
    {
        return $this->protection;
    }

    /**
     * @return array<string>
     */
    protected function getCombinationsToRemove(): array
    {
        return $this->combinationsToRemove;
    }

    private function readTokenFromFile(string $tokenFilePath): ?string
    {
        try {
            $baseDir = $this->resolveTokenBaseDir();

            // Resolve the base directory to its real path
            $realBaseDir = realpath($baseDir);

            // Normalize to use forward slashes and ensure trailing separator
            $realBaseDir = rtrim(str_replace('\\', '/', $realBaseDir), '/') . '/';

            // Build the full path (tokenFilePath is relative to the base directory)
            $fullPath = $baseDir . DIRECTORY_SEPARATOR . $tokenFilePath;

            // Resolve the real path to prevent directory traversal attacks
            $realPath = realpath($fullPath);

            // Normalize to use forward slashes
            $realPath = str_replace('\\', '/', $realPath);

            // Verify the resolved path is within the base directory.
            // Using trailing slash ensures we don't match paths that start with the base path as a prefix.
            if ( ! str_starts_with($realPath . '/', $realBaseDir)) {
                return null;
            }

            // Check if it's a file (not a directory)
            if ( ! is_file($realPath)) {
                return null;
            }

            // Read the file content.
            // Safe\file_get_contents() throws on failure (it never returns false), so no false check is needed.
            $content = file_get_contents($realPath);

            // Trim whitespace and newlines
            $token = trim($content);

            // Validate the token format using the very same definition as the option (single source of
            // truth). An invalid token read from the file falls back to prompting, consistent with
            // GitHubTokenCommandOption.
            if (false === $this->gitHubTokenCommandOption->isValidFormat($token)) {
                return null;
            }

            return $token;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Builds the ordered list of candidate folders where the workflows may live.
     *
     * Resolution order (first existing folder wins, handled by the Reader/Finder):
     *   1. `--workflows-dir` (CLI)
     *   2. `GHMatrixConfig::getWorkflowsDir()` (config)
     *   3. `--project-dir` (CLI)                → `<project-dir>/.github/workflows`
     *   4. `GHMatrixConfig::getProjectDir()`    → `<projectDir>/.github/workflows`
     *   5. git root (`RepoReader::getRepoRoot()`, wrapped) → `<root>/.github/workflows`
     *
     * The package's own `__DIR__` fallbacks are appended LAST by the Finder itself, so callers never
     * need to know about them. With nothing declared, this returns an empty list and the Finder falls
     * back to those package locations: behaviour is identical to before.
     *
     * @return array<int, string>
     */
    private function resolveWorkflowCandidates(?InputInterface $input): array
    {
        $candidates = [];

        // Priority 1: --workflows-dir (CLI)
        $cliWorkflowsDir = null !== $input ? $this->workflowsDirCommandOption->getValueOrNull($input) : null;
        if (null !== $cliWorkflowsDir) {
            $candidates[] = $cliWorkflowsDir;
        }

        // Priority 2: GHMatrixConfig::getWorkflowsDir() (config)
        $configWorkflowsDir = $this->config->getWorkflowsDir();
        if (null !== $configWorkflowsDir) {
            $candidates[] = $configWorkflowsDir;
        }

        // Priority 3: --project-dir (CLI) → <project-dir>/.github/workflows
        $cliProjectDir = null !== $input ? $this->projectDirCommandOption->getValueOrNull($input) : null;
        if (null !== $cliProjectDir) {
            $candidates[] = $this->workflowsDirFromProjectDir($cliProjectDir);
        }

        // Priority 4: GHMatrixConfig::getProjectDir() (config) → <projectDir>/.github/workflows
        $configProjectDir = $this->config->getProjectDir();
        if (null !== $configProjectDir) {
            $candidates[] = $this->workflowsDirFromProjectDir($configProjectDir);
        }

        // Priority 5: git root → <root>/.github/workflows
        try {
            $candidates[] = $this->workflowsDirFromProjectDir($this->repoReader->getRepoRoot());
        } catch (\Throwable) {
            // git not available or not in a git repository — the Finder falls back to the package locations.
        }

        return $candidates;
    }

    private function workflowsDirFromProjectDir(string $projectDir): string
    {
        return rtrim($projectDir, '/\\') . DIRECTORY_SEPARATOR . '.github' . DIRECTORY_SEPARATOR . 'workflows';
    }

    /**
     * Returns the base directory used to resolve the token file path.
     *
     * Resolution chain:
     *   1. `GHMatrixConfig::getProjectDir()` — the configured project root (when set).
     *   2. Git root (`RepoReader::getRepoRoot()`) — existing behaviour when git is available.
     *   3. Current working directory (`getcwd()`) — fallback for environments without git.
     */
    private function resolveTokenBaseDir(): string
    {
        // Priority 1: configured project dir (preferred base, when set)
        $projectDir = $this->config->getProjectDir();
        if (null !== $projectDir) {
            return $projectDir;
        }

        // Priority 2: git root (existing behaviour, when available)
        try {
            return $this->repoReader->getRepoRoot();
        } catch (\Throwable) {
            // git not available or not in a git repository — fall through
        }

        // Priority 3: current working directory (where the config file is already loaded from)
        return getcwd();
    }
}
