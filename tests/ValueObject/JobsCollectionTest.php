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

namespace Aerendir\Bin\GitHubActionsMatrix\Tests\ValueObject;

use Aerendir\Bin\GitHubActionsMatrix\ValueObject\Job;
use Aerendir\Bin\GitHubActionsMatrix\ValueObject\JobsCollection;
use Aerendir\Bin\GitHubActionsMatrix\ValueObject\Matrix;
use PHPUnit\Framework\TestCase;

class JobsCollectionTest extends TestCase
{
    public function testAddOrMergeJobAddsNewJob(): void
    {
        $matrix     = new Matrix([]);
        $collection = new JobsCollection();
        $collection->addJob(new Job('initial-job', $matrix));

        $job = new Job('new-job', $matrix);
        $collection->addOrMergeJob($job);

        $this->assertTrue($collection->hasJob('new-job'));
        $this->assertSame($job, $collection->getJob('new-job'));
        $this->assertCount(2, $collection->getJobs());
    }

    public function testAddOrMergeJobMergesMatrix(): void
    {
        $matrix1 = Matrix::createFromArray(['php' => ['8.3']], 'workflow.yml', 'Test Workflow', 'tests');
        $matrix2 = Matrix::createFromArray(['php' => ['8.4']], 'workflow2.yml', 'Test Workflow', 'tests');

        $job1 = new Job('existing-job', $matrix1);
        $job2 = new Job('existing-job', $matrix2);

        $collection = new JobsCollection();
        $collection->addJob($job1);

        $collection->addOrMergeJob($job2);

        $this->assertTrue($collection->hasJob('existing-job'));
        $jobMatrix    = $collection->getJob('existing-job')->getMatrix();
        $combinations = $jobMatrix->getCombinations();

        $this->assertCount(2, $combinations);
        $this->assertArrayHasKey('tests (8.3)', $combinations);
        $this->assertArrayHasKey('tests (8.4)', $combinations);

        $jobsIds = $collection->getJobsIds();
        $this->assertCount(2, $jobsIds);
        $this->assertEquals(['tests (8.3)', 'tests (8.4)'], $jobsIds);
    }

    public function testAddJobSuccessfully(): void
    {
        $matrix     = new Matrix([]);
        $job        = new Job('test-job', $matrix);
        $collection = new JobsCollection();

        $collection->addJob($job);

        $this->assertTrue($collection->hasJob('test-job'));
        $this->assertSame($job, $collection->getJob('test-job'));
        $this->assertCount(1, $collection->getJobs());
    }

    public function testAddDuplicateJobThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The job "test-job" already exists in the collection.');

        $matrix     = new Matrix([]);
        $job        = new Job('test-job', $matrix);
        $collection = new JobsCollection();

        $collection->addJob($job);
        $collection->addJob($job); // This should throw the exception
    }

    public function testMergeCollectionAddsJobsSuccessfully(): void
    {
        $matrix1 = Matrix::createFromArray(['php' => ['8.3']], 'workflow1.yml', 'Workflow 1', 'tests');
        $matrix2 = Matrix::createFromArray(['php' => ['8.4']], 'workflow2.yml', 'Workflow 2', 'tests');

        $job1 = new Job('job-1', $matrix1);
        $job2 = new Job('job-2', $matrix2);

        $collection1 = new JobsCollection();
        $collection1->addJob($job1);

        $collection2 = new JobsCollection();
        $collection2->addJob($job2);

        $collection1->mergeCollection($collection2);

        $this->assertCount(2, $collection1->getJobs());
        $this->assertTrue($collection1->hasJob('job-1'));
        $this->assertTrue($collection1->hasJob('job-2'));
    }

    public function testMergeCollectionMergesExistingJobs(): void
    {
        $matrix1 = Matrix::createFromArray(['php' => ['8.3']], 'workflow1.yml', 'Workflow 1', 'tests');
        $matrix2 = Matrix::createFromArray(['php' => ['8.4']], 'workflow2.yml', 'Workflow 2', 'tests');

        $job1 = new Job('job-1', $matrix1);
        $job2 = new Job('job-1', $matrix2);

        $collection1 = new JobsCollection();
        $collection1->addJob($job1);

        $collection2 = new JobsCollection();
        $collection2->addJob($job2);

        $collection1->mergeCollection($collection2);

        $this->assertCount(1, $collection1->getJobs());
        $this->assertTrue($collection1->hasJob('job-1'));

        $mergedMatrix = $collection1->getJob('job-1')->getMatrix()->getCombinations();
        $this->assertCount(2, $mergedMatrix);
        $this->assertArrayHasKey('tests (8.3)', $mergedMatrix);
        $this->assertArrayHasKey('tests (8.4)', $mergedMatrix);
    }

    public function testMergeCollectionWithEmptyCollection(): void
    {
        $matrix = Matrix::createFromArray(['php' => ['8.3']], 'workflow.yml', 'Workflow', 'tests');
        $job    = new Job('job-1', $matrix);

        $collection1 = new JobsCollection();
        $collection1->addJob($job);

        $collection2 = new JobsCollection(); // Empty collection

        $collection1->mergeCollection($collection2);

        $this->assertCount(1, $collection1->getJobs());
        $this->assertTrue($collection1->hasJob('job-1'));
    }
}
