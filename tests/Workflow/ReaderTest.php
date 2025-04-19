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

namespace Aerendir\Bin\GitHubActionsMatrix\Tests\Workflow;

use Aerendir\Bin\GitHubActionsMatrix\Tests\TestCase;
use Aerendir\Bin\GitHubActionsMatrix\Workflow\Finder;
use Aerendir\Bin\GitHubActionsMatrix\Workflow\Reader;

use function Safe\unlink;

class ReaderTest extends TestCase
{
    public function testCreateFromYamlThrowsExceptionForNonArrayYaml(): void
    {
        $yamlContent = 'Invalid YAML';
        $fileInfo    = $this->createTempFile($yamlContent);

        $reader = new Reader();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The parsed YAML file is not an array.');

        $reader->createFromYaml($fileInfo);

        unlink($fileInfo->getPathname());
    }

    public function testCreateFromYamlThrowsExceptionIfNameKeyIsMissing(): void
    {
        $yamlContent = <<<YAML
            jobs:
              build:
                runs-on: ubuntu-latest
                steps:
                  - name: Checkout
                    uses: actions/checkout@v3
            YAML;

        $fileInfo = $this->createTempFile($yamlContent);

        $reader = new Reader();

        $this->expectException(\RuntimeException::class);
        $reader->createFromYaml($fileInfo);

        unlink($fileInfo->getPathname());
    }

    public function testCreateFromYamlThrowsExceptionIfJobsKeyIsMissing(): void
    {
        $yamlContent = <<<YAML
            name: Test Workflow
            YAML;

        $fileInfo = $this->createTempFile($yamlContent);

        $reader = new Reader();

        $this->expectException(\RuntimeException::class);
        $reader->createFromYaml($fileInfo);

        unlink($fileInfo->getPathname());
    }

    public function testCreateFromYamlReturnsJobsCollection(): void
    {
        $yamlContent = <<<YAML
            name: Test Workflow
            on: [push]
            jobs:
              build:
                strategy:
                  fail-fast: false
                  matrix:
                    os: [ ubuntu-latest ]
                    php: [ '8.3', '8.4' ]
                    symfony: [ '~6.4', '~7.4' ]
                steps:
                  - name: Checkout
                    uses: actions/checkout@v3
            YAML;

        $fileInfo = $this->createTempFile($yamlContent);

        $reader         = new Reader();
        $jobsCollection = $reader->createFromYaml($fileInfo);
        $jobs           = $jobsCollection->getJobs();

        $this->assertCount(1, $jobs);
        $this->assertTrue($jobsCollection->hasJob('build'));

        unlink($fileInfo->getPathname());
    }

    public function testReadProcessesValidWorkflowsSuccessfully(): void
    {
        $phpCsWorkflowContent = <<<YAML
            name: PHO CS
            on: [push]
            jobs:
              phpcs:
                strategy:
                  fail-fast: false
                  matrix:
                    os: [ ubuntu-latest ]
                    php: [ '8.3', '8.4' ]
                    symfony: [ '~6.4', '~7.4' ]
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
                    os: [ ubuntu-latest ]
                    php: [ '8.3', '8.4' ]
                    symfony: [ '~6.4', '~7.4' ]
                steps:
                  - name: Checkout
                    uses: actions/checkout@v3
            YAML;

        $phpCsFileInfo  = $this->createTempFile($phpCsWorkflowContent, 'phpcs');
        $rectorFileInfo = $this->createTempFile($rectorWorkflowContent, 'rector');

        $finder = $this->createMock(Finder::class);
        $finder->method('getWorkflows')->willReturn(new \ArrayIterator([$phpCsFileInfo, $rectorFileInfo]));

        $reader = new Reader($finder);

        $jobsCollection = $reader->read();

        $this->assertCount(2, $jobsCollection->getJobs());
        $this->assertTrue($jobsCollection->hasJob('phpcs'));
        $this->assertTrue($jobsCollection->hasJob('rector'));

        unlink($phpCsFileInfo->getPathname());
        unlink($rectorFileInfo->getPathname());
    }
}
