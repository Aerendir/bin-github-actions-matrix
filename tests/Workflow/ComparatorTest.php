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

use Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig;
use Aerendir\Bin\GitHubActionsMatrix\ValueObject\Job;
use Aerendir\Bin\GitHubActionsMatrix\ValueObject\JobsCollection;
use Aerendir\Bin\GitHubActionsMatrix\ValueObject\Matrix;
use Aerendir\Bin\GitHubActionsMatrix\Workflow\Comparator;
use PHPUnit\Framework\TestCase;

class ComparatorTest extends TestCase
{
    public function testCompareIdentifiesJobsToRemove(): void
    {
        $phpCsMatrix  = Matrix::createFromArray(['php' => ['8.3']], 'phpcs.yml', 'Test PHP CS Fixer Workflow', 'phpcs');
        $rectorMatrix = Matrix::createFromArray(['php' => ['8.3']], 'rector.yml', 'Test Rector Workflow', 'rector');

        $phpCsJob  = new Job('phpcs', $phpCsMatrix);
        $rectorJob = new Job('rector', $rectorMatrix);

        $localJobsCollection = new JobsCollection();
        $localJobsCollection->addJob($phpCsJob);
        $localJobsCollection->addJob($rectorJob);

        $remoteJobIds = [
            'rector (8.2)',
            'rector (8.3)',
        ];

        $comparator   = new Comparator();
        $jobsToRemove = $comparator->compare($localJobsCollection, $remoteJobIds);

        $this->assertEquals(['rector (8.2)'], $jobsToRemove);
    }

    public function testCompareReturnsEmptyWhenJobsAreInSync(): void
    {
        $phpCsMatrix  = Matrix::createFromArray(['php' => ['8.3']], 'phpcs.yml', 'Test PHP CS Fixer Workflow', 'phpcs');
        $rectorMatrix = Matrix::createFromArray(['php' => ['8.3']], 'rector.yml', 'Test Rector Workflow', 'rector');

        $phpCsJob  = new Job('phpcs', $phpCsMatrix);
        $rectorJob = new Job('rector', $rectorMatrix);

        $localJobsCollection = new JobsCollection();
        $localJobsCollection->addJob($phpCsJob);
        $localJobsCollection->addJob($rectorJob);

        $remoteJobIds = [
            'phpcs (8.3)',
            'rector (8.3)',
        ];

        $comparator   = new Comparator();
        $jobsToRemove = $comparator->compare($localJobsCollection, $remoteJobIds);

        $this->assertEquals([], $jobsToRemove);
    }

    public function testCompareIdentifiesJobsToSync(): void
    {
        $phpCsMatrix  = Matrix::createFromArray(['php' => ['8.3']], 'phpcs.yml', 'Test PHP CS Fixer Workflow', 'phpcs');
        $rectorMatrix = Matrix::createFromArray(['php' => ['8.3']], 'rector.yml', 'Test Rector Workflow', 'rector');

        $phpCsJob  = new Job('phpcs', $phpCsMatrix);
        $rectorJob = new Job('rector', $rectorMatrix);

        $localJobsCollection = new JobsCollection();
        $localJobsCollection->addJob($phpCsJob);
        $localJobsCollection->addJob($rectorJob);

        $comparator   = new Comparator();
        $comparator->compare($localJobsCollection, ['rector (8.3)']);

        $this->assertTrue($phpCsMatrix->getCombinations()['phpcs (8.3)']->isToSync());
        $this->assertFalse($rectorMatrix->getCombinations()['rector (8.3)']->isToSync());
    }

    public function testCompareMarksOptionalCombinationsCorrectly(): void
    {
        $config = new GHMatrixConfig();
        $config->markOptionalCombination('phpunit', ['php' => '8.4']);

        $phpunitMatrix = Matrix::createFromArray(['php' => ['8.3', '8.4']], 'phpunit.yml', 'PHPUnit Tests', 'phpunit');
        $phpunitJob    = new Job('phpunit', $phpunitMatrix);

        $localJobsCollection = new JobsCollection();
        $localJobsCollection->addJob($phpunitJob);

        $comparator = new Comparator($config);
        $comparator->compare($localJobsCollection, []);

        $combinations = $phpunitMatrix->getCombinations();
        $this->assertFalse($combinations['phpunit (8.3)']->isOptional());
        $this->assertTrue($combinations['phpunit (8.4)']->isOptional());
    }

    public function testCompareThrowsExceptionForInvalidOptionalCombination(): void
    {
        $config = new GHMatrixConfig();
        $config->markOptionalCombination('phpunit', ['php' => '8.2']);

        $phpunitMatrix = Matrix::createFromArray(['php' => ['8.3', '8.4']], 'phpunit.yml', 'PHPUnit Tests', 'phpunit');
        $phpunitJob    = new Job('phpunit', $phpunitMatrix);

        $localJobsCollection = new JobsCollection();
        $localJobsCollection->addJob($phpunitJob);

        $comparator = new Comparator($config);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The optional combination {"php":"8.2"} for workflow "phpunit" does not exist in the matrix or is explicitly excluded.');

        $comparator->compare($localJobsCollection, []);
    }

    public function testCompareThrowsExceptionForExcludedOptionalCombination(): void
    {
        $config = new GHMatrixConfig();
        $config->markOptionalCombination('phpunit', ['php' => '8.3', 'symfony' => '~6.4']);

        $matrix = [
            'php'     => ['8.3', '8.4'],
            'symfony' => ['~6.4', '~7.4'],
            'exclude' => [
                ['php' => '8.3', 'symfony' => '~6.4'],
            ],
        ];
        $phpunitMatrix = Matrix::createFromArray($matrix, 'phpunit.yml', 'PHPUnit Tests', 'phpunit');
        $phpunitJob    = new Job('phpunit', $phpunitMatrix);

        $localJobsCollection = new JobsCollection();
        $localJobsCollection->addJob($phpunitJob);

        $comparator = new Comparator($config);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The optional combination {"php":"8.3","symfony":"~6.4"} for workflow "phpunit" does not exist in the matrix or is explicitly excluded.');

        $comparator->compare($localJobsCollection, []);
    }

    public function testCompareSupportsOptionalCombinationsWithPartialMatch(): void
    {
        $config = new GHMatrixConfig();
        $config->markOptionalCombination('phpunit', ['php' => '8.4']);

        $matrix = [
            'php'     => ['8.3', '8.4'],
            'symfony' => ['~6.4', '~7.4'],
        ];
        $phpunitMatrix = Matrix::createFromArray($matrix, 'phpunit.yml', 'PHPUnit Tests', 'phpunit');
        $phpunitJob    = new Job('phpunit', $phpunitMatrix);

        $localJobsCollection = new JobsCollection();
        $localJobsCollection->addJob($phpunitJob);

        $comparator = new Comparator($config);
        $comparator->compare($localJobsCollection, []);

        $combinations = $phpunitMatrix->getCombinations();
        // PHP 8.4 with any symfony version should be marked as optional
        $this->assertFalse($combinations['phpunit (8.3, ~6.4)']->isOptional());
        $this->assertFalse($combinations['phpunit (8.3, ~7.4)']->isOptional());
        $this->assertTrue($combinations['phpunit (8.4, ~6.4)']->isOptional());
        $this->assertTrue($combinations['phpunit (8.4, ~7.4)']->isOptional());
    }

    public function testCompareExcludesOptionalCombinationsFromJobIds(): void
    {
        $config = new GHMatrixConfig();
        $config->markOptionalCombination('phpunit', ['php' => '8.4']);

        $phpunitMatrix = Matrix::createFromArray(['php' => ['8.3', '8.4']], 'phpunit.yml', 'PHPUnit Tests', 'phpunit');
        $phpunitJob    = new Job('phpunit', $phpunitMatrix);

        $localJobsCollection = new JobsCollection();
        $localJobsCollection->addJob($phpunitJob);

        $comparator = new Comparator($config);
        $comparator->compare($localJobsCollection, []);

        $jobIds = $localJobsCollection->getJobsIds();
        $this->assertContains('phpunit (8.3)', $jobIds);
        $this->assertNotContains('phpunit (8.4)', $jobIds);
    }
}
