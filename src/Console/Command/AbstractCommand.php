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
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\RepoBranchCommandOption;
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
use function Safe\preg_match;
use function Safe\realpath;

abstract class AbstractCommand extends Command
{
    protected readonly string $branchName;
    private readonly GHMatrixConfig $config;
    private readonly GitHubUsernameCommandOption $gitHubUsernameCommandOption;
    private readonly GitHubTokenCommandOption $gitHubTokenCommandOption;
    private readonly RepoBranchCommandOption $repoBranchCommandOption;
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
        ?RepoReader $repoReader = null,
        ?WorkflowsReader $workflowsReader = null,
        ?Comparator $comparator = null,
        ?Client $githubClient = null,
    ) {
        parent::__construct();
        $this->config                      = $config                      ?? new GHMatrixConfig();
        $this->gitHubUsernameCommandOption = $gitHubUsernameCommandOption ?? new GitHubUsernameCommandOption();
        $this->gitHubTokenCommandOption    = $gitHubTokenCommandOption    ?? new GitHubTokenCommandOption();
        $this->repoBranchCommandOption     = $repoBranchCommandOption     ?? new RepoBranchCommandOption();
        $this->repoReader                  = $repoReader                  ?? new RepoReader();
        $this->workflowsReader             = $workflowsReader             ?? new WorkflowsReader();
        $this->comparator                  = $comparator                  ?? new Comparator();
        $this->githubClient                = $githubClient                ?? Client::createWithHttpClient(new HttplugClient());
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption(GitHubUsernameCommandOption::NAME, GitHubUsernameCommandOption::SHORTCUT, InputOption::VALUE_REQUIRED, 'Your GitHub username.');
        $this->addOption(GitHubTokenCommandOption::NAME, GitHubTokenCommandOption::SHORTCUT, InputOption::VALUE_REQUIRED, 'Your GitHub access token.');
        $this->addOption(RepoBranchCommandOption::NAME, RepoBranchCommandOption::SHORTCUT, InputOption::VALUE_REQUIRED, 'The branch for which the matrix has to be synchronized.');
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
        $this->repoName = $this->repoReader->getRepoName();

        $this->localJobs = $this->workflowsReader->read();

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

    protected function getRepoName(): string
    {
        return $this->repoName;
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
            // Get the repository root
            $repoRoot = $this->repoReader->getRepoRoot();

            // Resolve the repository root to its real path
            $realRepoRoot = realpath($repoRoot);

            // Normalize to use forward slashes and ensure trailing separator
            $realRepoRoot = rtrim(str_replace('\\', '/', $realRepoRoot), '/') . '/';

            // Build the full path (tokenFilePath is relative to repo root)
            $fullPath = $repoRoot . DIRECTORY_SEPARATOR . $tokenFilePath;

            // Resolve the real path to prevent directory traversal attacks
            $realPath = realpath($fullPath);

            // Normalize to use forward slashes
            $realPath = str_replace('\\', '/', $realPath);

            // Verify the resolved path is within the repository root
            // Using trailing slash ensures we don't match paths that start with repo path as prefix
            if ( ! str_starts_with($realPath . '/', $realRepoRoot)) {
                return null;
            }

            // Check if it's a file (not a directory)
            if ( ! is_file($realPath)) {
                return null;
            }

            // Read the file content
            $content = file_get_contents($realPath);
            if (false === $content) {
                return null;
            }

            // Trim whitespace and newlines
            $token = trim($content);

            // Validate the token format using the same validation as the option
            preg_match('/^ghp_[A-Za-z0-9]{36}$/', $token);

            return $token;
        } catch (\Throwable) {
            return null;
        }
    }
}
