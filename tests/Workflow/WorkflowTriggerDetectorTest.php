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
use Aerendir\Bin\GitHubActionsMatrix\Workflow\WorkflowTriggerDetector;

final class WorkflowTriggerDetectorTest extends TestCase
{
    public function testDetectsAStringTrigger(): void
    {
        $detector = new WorkflowTriggerDetector();

        $this->assertSame(['push'], $detector->detectTriggers(['on' => 'push']));
    }

    public function testDetectsListTriggersNormalizedAndDeduplicated(): void
    {
        $detector = new WorkflowTriggerDetector();

        $this->assertSame(
            ['workflow_dispatch', 'pull_request'],
            $detector->detectTriggers(['on' => ['  Workflow_Dispatch ', 'PULL_REQUEST', 'pull_request']])
        );
    }

    public function testDetectsMapTriggersFromTheKeys(): void
    {
        $detector = new WorkflowTriggerDetector();

        $this->assertSame(
            ['push', 'schedule'],
            $detector->detectTriggers(['on' => ['push' => null, 'schedule' => [['cron' => '0 0 * * *']]]])
        );
    }

    public function testReturnsNoTriggersWhenTheOnKeyIsMissing(): void
    {
        $detector = new WorkflowTriggerDetector();

        // No "on" key at all: nothing to derive (covers the `default` match arm).
        $this->assertSame([], $detector->detectTriggers(['name' => 'CI', 'jobs' => []]));
    }

    public function testSkipsNonScalarAndEmptyTriggerEntries(): void
    {
        $detector = new WorkflowTriggerDetector();

        // A nested array is not a valid event name (skipped), as is an empty/whitespace-only string.
        $this->assertSame(
            ['push'],
            $detector->detectTriggers(['on' => ['push', ['nested' => 'value'], '   ']])
        );
    }

    public function testIsPullRequestEligibleWhenAnEligibleTriggerIsPresent(): void
    {
        $detector = new WorkflowTriggerDetector();

        $this->assertTrue($detector->isPullRequestEligible(['schedule', 'pull_request']));
        $this->assertTrue($detector->isPullRequestEligible(['push']));
        $this->assertTrue($detector->isPullRequestEligible(['pull_request_target']));
    }

    public function testIsNotPullRequestEligibleWithoutAnEligibleTrigger(): void
    {
        $detector = new WorkflowTriggerDetector();

        $this->assertFalse($detector->isPullRequestEligible(['schedule', 'workflow_dispatch']));
        $this->assertFalse($detector->isPullRequestEligible([]));
    }
}
