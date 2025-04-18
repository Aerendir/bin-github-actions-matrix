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

use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\GitHubTokenCommandOption;
use Aerendir\Bin\GitHubActionsMatrix\Console\Command\Params\Options\GitHubUsernameCommandOption;
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

abstract class AbstractCommand extends Command
{
    private readonly GitHubUsernameCommandOption $gitHubUsernameCommandOption;
    private readonly GitHubTokenCommandOption $gitHubTokenCommandOption;
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
        GitHubUsernameCommandOption $gitHubUsernameCommandOption = null,
        GitHubTokenCommandOption $gitHubTokenCommandOption = null,
        RepoReader $repoReader = null,
        WorkflowsReader $workflowsReader = null,
        Comparator $comparator = null
    ) {
        parent::__construct();
        $this->gitHubUsernameCommandOption = $gitHubUsernameCommandOption ??new GitHubUsernameCommandOption();
        $this->gitHubTokenCommandOption    = $gitHubTokenCommandOption    ??new GitHubTokenCommandOption();
        $this->repoReader                  = $repoReader                  ?? new RepoReader();
        $this->workflowsReader             = $workflowsReader             ?? new WorkflowsReader();
        $this->comparator                  = $comparator                  ?? new Comparator();
        $this->githubClient                = Client::createWithHttpClient(new HttplugClient());
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption(GitHubUsernameCommandOption::OPT_REPO_USERNAME, GitHubUsernameCommandOption::OPT_REPO_USERNAME_SHORTCUT, InputOption::VALUE_REQUIRED, 'Your GitHub username.');
        $this->addOption(GitHubTokenCommandOption::OPT_REPO_TOKEN, GitHubTokenCommandOption::OPT_REPO_TOKEN_SHORTCUT, InputOption::VALUE_REQUIRED, 'Your GitHub access token.');
    }

    protected function init(InputInterface $input, OutputInterface $output): void
    {
        $questionHelper = $this->getHelper('question');

        if (false === $questionHelper instanceof QuestionHelper) {
            throw new \RuntimeException(sprintf('The helper %s is not available.', QuestionHelper::class));
        }

        // 'Cannot get the username from the git config. Pass it explicitly using option "--username"'
        $repoUsername   = $this->getRepoUsername($input, $output, $questionHelper);
        $repoToken      = $this->gitHubTokenCommandOption->getValueOrAsk($input, $output, $questionHelper);
        $this->repoName = $this->repoReader->getRepoName();

        $this->localJobs = $this->workflowsReader->read();

        $this->githubClient->authenticate(tokenOrLogin: $repoToken, authMethod: AuthMethod::ACCESS_TOKEN);
        $repo = $this->githubClient->api('repo');
        if (false === $repo instanceof Repo) {
            throw new \RuntimeException('The API returned an unexpected object');
        }
        $this->protection      = $repo->protection();
        $protectionRules       = $this->protection->show($repoUsername, $this->repoName, 'dev');

        $requiredStatusChecks = $protectionRules['required_status_checks'];
        $this->remoteJobsIds  = $requiredStatusChecks['contexts'];

        $this->combinationsToRemove = $this->comparator->compare($this->localJobs, $this->remoteJobsIds);
    }

    protected function getRepoUsername(InputInterface $input = null, OutputInterface $output = null, QuestionHelper $questionHelper = null): string
    {
        if (isset($this->repoUsername)) {
            return $this->repoUsername;
        }

        $repoUsername = $this->repoReader->getUsername();
        if (null !== $repoUsername) {
            return $this->repoUsername = $repoUsername;
        }

        if (null === $input || null === $output || null === $questionHelper) {
            throw new \RuntimeException('You must pass the input and output objects and the question helper to get the username.');
        }

        return $this->repoUsername = $this->gitHubUsernameCommandOption->getValueOrAsk($input, $output, $questionHelper);
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
}
