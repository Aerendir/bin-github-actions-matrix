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
}
