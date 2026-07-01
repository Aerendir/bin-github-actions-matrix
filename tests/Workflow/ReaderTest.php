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
use function Aerendir\Bin\GitHubActionsMatrix\Tests\Functions\unlink;

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

    public function testCreateFromYamlCreatesBareNameContextForNonMatrixJob(): void
    {
        // A realistic non-matrix workflow (like the monorepo's copilot-setup-steps / next-build): no
        // strategy, so its single required-check context is the bare job name.
        $yamlContent = <<<YAML
            name: Build
            on: [push]
            jobs:
              build:
                runs-on: ubuntu-latest
                steps:
                  - name: Checkout
                    uses: actions/checkout@v3
            YAML;

        $fileInfo = $this->createTempFile($yamlContent);

        $reader         = new Reader();
        $jobsCollection = $reader->createFromYaml($fileInfo);
        $combinations   = $jobsCollection->getJob('build')->getMatrix()->getCombinations();

        $this->assertCount(1, $combinations);
        // The combination is keyed by its context string: bare "build", no "(...)" suffix.
        $this->assertArrayHasKey('build', $combinations);

        unlink($fileInfo->getPathname());
    }

    public function testCreateFromYamlSkipsIgnoredJobs(): void
    {
        $yamlContent = <<<YAML
            name: CI
            on: [push]
            jobs:
              phpunit:
                strategy:
                  matrix:
                    php: [ '8.3', '8.4' ]
                steps:
                  - uses: actions/checkout@v3
              deploy:
                runs-on: ubuntu-latest
                steps:
                  - uses: actions/checkout@v3
            YAML;

        $fileInfo = $this->createTempFile($yamlContent);

        $reader         = new Reader();
        $jobsCollection = $reader->createFromYaml($fileInfo, ['deploy']);

        $this->assertTrue($jobsCollection->hasJob('phpunit'));
        $this->assertFalse($jobsCollection->hasJob('deploy'));

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

    public function testCreateFromYamlWarnsAndSkipsAJobWithAnInterpolatedName(): void
    {
        $yamlContent = <<<YAML
            name: CI
            on: [push]
            jobs:
              build:
                name: build \${{ matrix.os }}
                strategy:
                  matrix:
                    os: [ ubuntu-latest, windows-latest ]
                steps:
                  - uses: actions/checkout@v3
            YAML;

        $fileInfo = $this->createTempFile($yamlContent);

        $reader         = new Reader();
        $jobsCollection = $reader->createFromYaml($fileInfo);

        // The non-derivable job is not added (no miscomputed context) and a clear warning is recorded.
        $this->assertFalse($jobsCollection->hasJob('build'));
        $this->assertCount(1, $jobsCollection->getWarnings());
        $this->assertStringContainsString('build', $jobsCollection->getWarnings()[0]);
        $this->assertStringContainsString('addRequiredCheck()', $jobsCollection->getWarnings()[0]);

        unlink($fileInfo->getPathname());
    }

    public function testCreateFromYamlWarnsAndSkipsAJobWithADynamicMatrix(): void
    {
        $yamlContent = <<<YAML
            name: CI
            on: [push]
            jobs:
              test:
                strategy:
                  matrix:
                    os: \${{ fromJson(needs.setup.outputs.os) }}
                steps:
                  - uses: actions/checkout@v3
            YAML;

        $fileInfo = $this->createTempFile($yamlContent);

        $reader         = new Reader();
        $jobsCollection = $reader->createFromYaml($fileInfo);

        $this->assertFalse($jobsCollection->hasJob('test'));
        $this->assertCount(1, $jobsCollection->getWarnings());

        unlink($fileInfo->getPathname());
    }

    public function testCreateFromYamlKeepsDerivableJobsWhileSkippingNonDerivableOnes(): void
    {
        $yamlContent = <<<YAML
            name: CI
            on: [push]
            jobs:
              phpunit:
                strategy:
                  matrix:
                    php: [ '8.3', '8.4' ]
                steps:
                  - uses: actions/checkout@v3
              dynamic:
                strategy:
                  matrix:
                    os: \${{ fromJson(needs.setup.outputs.os) }}
                steps:
                  - uses: actions/checkout@v3
            YAML;

        $fileInfo = $this->createTempFile($yamlContent);

        $reader         = new Reader();
        $jobsCollection = $reader->createFromYaml($fileInfo);

        $this->assertTrue($jobsCollection->hasJob('phpunit'));
        $this->assertFalse($jobsCollection->hasJob('dynamic'));
        $this->assertCount(1, $jobsCollection->getWarnings());

        unlink($fileInfo->getPathname());
    }

    public function testReadInjectsExternalRequiredChecksAsBareNameJobs(): void
    {
        $workflowContent = <<<YAML
            name: CI
            on: [push]
            jobs:
              phpcs:
                strategy:
                  matrix:
                    php: [ '8.3', '8.4' ]
                steps:
                  - uses: actions/checkout@v3
            YAML;

        $fileInfo = $this->createTempFile($workflowContent, 'phpcs');

        $finder = $this->createMock(Finder::class);
        $finder->method('getWorkflows')->willReturn(new \ArrayIterator([$fileInfo]));

        $reader         = new Reader($finder);
        $jobsCollection = $reader->read([], [], ['codecov']);

        $this->assertTrue($jobsCollection->hasJob('phpcs'));
        // The external check is present as a bare-name job whose single context equals the check name.
        $this->assertTrue($jobsCollection->hasJob('codecov'));
        $this->assertArrayHasKey('codecov', $jobsCollection->getJob('codecov')->getMatrix()->getCombinations());

        unlink($fileInfo->getPathname());
    }
}
