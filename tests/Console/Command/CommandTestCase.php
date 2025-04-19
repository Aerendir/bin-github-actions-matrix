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

namespace Aerendir\Bin\GitHubActionsMatrix\Tests\Console\Command;

use Aerendir\Bin\GitHubActionsMatrix\Repo\Reader as RepoReader;
use Aerendir\Bin\GitHubActionsMatrix\Tests\TestCase;
use Aerendir\Bin\GitHubActionsMatrix\Workflow\Finder;
use Aerendir\Bin\GitHubActionsMatrix\Workflow\Reader as WorkflowsReader;
use Github\Api\Repo;
use Github\Api\Repository\Protection;
use Github\Client;
use PHPUnit\Framework\MockObject\MockObject;

class CommandTestCase extends TestCase
{
    protected function createMockReader(string $testRepo): RepoReader
    {
        $mockReader = $this->createMock(RepoReader::class);
        $mockReader->method('getRepoName')->willReturn($testRepo);

        return $mockReader;
    }

    protected function createMockWorkflowsReader(): WorkflowsReader
    {
        $phpCsWorkflowContent = <<<YAML
            name: PHP CS
            on: [push]
            jobs:
              phpcs:
                strategy:
                  fail-fast: false
                  matrix:
                    php: [ '8.3', '8.4' ]
                steps:
                  - name: Checkout
                    uses: actions/checkout@v3
            YAML;

        $rectorWorkflowContent = <<<YAML
            name: Rector
            on: [push]
            jobs:
              rector:
                strategy:
                  fail-fast: false
                  matrix:
                    php: [ '8.3', '8.4' ]
                steps:
                  - name: Checkout
                    uses: actions/checkout@v3
            YAML;

        $phpCsFileInfo  = $this->createTempFile($phpCsWorkflowContent, 'phpcs');
        $rectorFileInfo = $this->createTempFile($rectorWorkflowContent, 'rector');

        $finder = $this->createMock(Finder::class);
        $finder->method('getWorkflows')->willReturn(new \ArrayIterator([$phpCsFileInfo, $rectorFileInfo]));

        return new WorkflowsReader($finder);
    }

    protected function createMockGitHubClient(): Client&MockObject
    {
        $mockProtection = $this->createMock(Protection::class);
        $mockProtection->method('show')->willReturn([
            'required_status_checks' => [
                'contexts' => [
                    'phpcs (8.2)',
                    'phpcs (8.3)',
                    'rector (8.2)',
                    'rector (8.3)',
                ],
            ],
        ]);

        $mockRepo = $this->createMock(Repo::class);
        $mockRepo->method('protection')->willReturn($mockProtection);

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('authenticate');
        $mockClient->method('api')->with('repo')->willReturn($mockRepo);

        return $mockClient;
    }
}
