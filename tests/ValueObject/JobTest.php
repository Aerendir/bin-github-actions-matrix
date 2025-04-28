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

use Aerendir\Bin\GitHubActionsMatrix\ValueObject\Combination;
use Aerendir\Bin\GitHubActionsMatrix\ValueObject\Job;
use PHPUnit\Framework\TestCase;

final class JobTest extends TestCase
{
    public function testCreateFromArrayCreatesJobSuccessfully(): void
    {
        $content = [
            'strategy' => [
                'matrix' => [
                    'php' => [
                        '8.0',
                        '8.1',
                    ],
                ],
            ],
        ];
        $workflowFilename = 'workflow.yml';
        $workflowName     = 'Example Workflow';
        $jobName          = 'test-job';

        $job = Job::createFromArray($jobName, $content, $workflowFilename, $workflowName, $jobName);
        $this->assertSame($jobName, $job->getName());

        $expectedCombinations = [
            'test-job (8.0)' => new Combination(
                ['php' => '8.0'],
                $workflowFilename,
                $workflowName,
                $jobName,
            ),
            'test-job (8.1)' => new Combination(
                ['php' => '8.1'],
                $workflowFilename,
                $workflowName,
                $jobName,
            ),
        ];
        $actualCombinations = $job->getMatrix()->getCombinations();

        $this->assertEquals($expectedCombinations, $actualCombinations);
    }

    public function testCreateFromArrayThrowsErrorIfStrategyKeyIsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $invalidContent = [
            // 'strategy' key is missing.
        ];

        Job::createFromArray('test-job', $invalidContent, 'workflow.yml', 'Example Workflow', 'test-job');
    }

    public function testCreateFromArrayThrowsErrorIfStrategyKeyIsNotArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $invalidContent = [
            'strategy' => 'not-an-array', // Invalid type.
        ];

        Job::createFromArray('test-job', $invalidContent, 'workflow.yml', 'Example Workflow', 'test-job');
    }

    public function testCreateFromArrayThrowsErrorIfMatrixKeyIsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $invalidContent = [
            'strategy' => [
                // 'matrix' key is missing.
            ],
        ];

        Job::createFromArray('test-job', $invalidContent, 'workflow.yml', 'Example Workflow', 'test-job');
    }

    public function testCreateFromArrayThrowsErrorIfMatrixKeyIsNotArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $invalidContent = [
            'strategy' => [
                'matrix' => 'not-an-array', // Invalid type.
            ],
        ];

        Job::createFromArray('test-job', $invalidContent, 'workflow.yml', 'Example Workflow', 'test-job');
    }
}
