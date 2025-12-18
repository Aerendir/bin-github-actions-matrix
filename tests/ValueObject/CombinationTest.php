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
use PHPUnit\Framework\TestCase;

class CombinationTest extends TestCase
{
    public function testToStringReturnsCorrectFormatForSingleElementCombination(): void
    {
        $combination      = ['PHP 8.3'];
        $workflowFilename = 'workflow.yml';
        $workflowName     = 'Test Workflow';
        $job              = 'unit-tests';

        $instance = new Combination($combination, $workflowFilename, $workflowName, $job);

        $this->assertSame('unit-tests (PHP 8.3)', (string) $instance);
    }

    public function testToStringReturnsCorrectFormatForMultipleElementCombination(): void
    {
        $combination = [
            'PHP 8.3',
            'PostgreSQL',
        ];
        $workflowFilename = 'workflow.yml';
        $workflowName     = 'Test Workflow';
        $job              = 'integration-tests';

        $instance = new Combination($combination, $workflowFilename, $workflowName, $job);

        $this->assertSame('integration-tests (PHP 8.3, PostgreSQL)', (string) $instance);
    }

    public function testToStringHandlesEmptyCombination(): void
    {
        $combination      = [];
        $workflowFilename = 'workflow.yml';
        $workflowName     = 'Test Workflow';
        $job              = 'artifact-build';

        $instance = new Combination($combination, $workflowFilename, $workflowName, $job);

        $this->assertSame('artifact-build ()', (string) $instance);
    }

    public function testGetCombinationReturnsCorrectValues(): void
    {
        $combination = [
            'PHP 8.3',
            'MySQL',
        ];
        $workflowFilename = 'workflow.yml';
        $workflowName     = 'Test Workflow';
        $job              = 'unit-tests';

        $instance = new Combination($combination, $workflowFilename, $workflowName, $job);

        $this->assertSame($combination, $instance->getCombination());
    }

    public function testGetWorkflowFilenameReturnsCorrectValue(): void
    {
        $combination = [
            'PHP 8.3',
            'Redis',
        ];
        $workflowFilename = 'test-workflow.yml';
        $workflowName     = 'New Workflow';
        $job              = 'e2e-tests';

        $instance = new Combination($combination, $workflowFilename, $workflowName, $job);

        $this->assertSame($workflowFilename, $instance->getWorkflowFilename());
    }

    public function testGetWorkflowNameReturnsCorrectValue(): void
    {
        $combination = [
            'PHP 8.1',
            'MongoDB',
        ];
        $workflowFilename = 'another-workflow.yml';
        $workflowName     = 'Sample Workflow';
        $job              = 'build-tests';

        $instance = new Combination($combination, $workflowFilename, $workflowName, $job);

        $this->assertSame($workflowName, $instance->getWorkflowName());
    }

    public function testGetJobReturnsCorrectValue(): void
    {
        $combination = [
            'PHP 8.0',
            'SQLite',
        ];
        $workflowFilename = 'job-test-workflow.yml';
        $workflowName     = 'Job Test Workflow';
        $job              = 'lint-checks';

        $instance = new Combination($combination, $workflowFilename, $workflowName, $job);

        $this->assertSame($job, $instance->getJob());
    }

    public function testSetToSyncEnablesSyncByDefault(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setToSync();

        $this->assertTrue($instance->isToSync());
    }

    public function testSetToRemoveEnablesRemoveByDefault(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setToRemove();

        $this->assertTrue($instance->isToRemove());
    }

    public function testSetToRemoveDisablesRemoveWhenSetToFalse(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setToRemove(false);

        $this->assertFalse($instance->isToRemove());
    }

    public function testSetToRemoveUpdatesBasedOnRepeatedCalls(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');

        $instance->setToRemove(true);
        $this->assertTrue($instance->isToRemove());

        $instance->setToRemove(false);
        $this->assertFalse($instance->isToRemove());
    }

    public function testSetToSyncDisablesSyncWhenSetToFalse(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setToSync(false);

        $this->assertFalse($instance->isToSync());
    }

    public function testSetToSyncUpdatesBasedOnRepeatedCalls(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');

        $instance->setToSync(true);
        $this->assertTrue($instance->isToSync());

        $instance->setToSync(false);
        $this->assertFalse($instance->isToSync());
    }

    public function testIsToRemoveReturnsTrueWhenSetToTrue(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setToRemove(true);

        $this->assertTrue($instance->isToRemove());
    }

    public function testIsToSyncReturnsTrueWhenSetToTrue(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setToSync(true);

        $this->assertTrue($instance->isToSync());
    }

    public function testIsToSyncReturnsFalseWhenSetToFalse(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setToSync(false);

        $this->assertFalse($instance->isToSync());
    }

    public function testIsToSyncDefaultsToFalse(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');

        $this->assertFalse($instance->isToSync());
    }

    public function testIsToRemoveReturnsFalseWhenSetToFalse(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setToRemove(false);

        $this->assertFalse($instance->isToRemove());
    }

    public function testIsToRemoveDefaultsToFalse(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');

        $this->assertFalse($instance->isToRemove());
    }

    public function testGetActionReturnsNothingByDefault(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');

        $this->assertSame(Combination::ACTION_NOTHING, $instance->getAction());
    }

    public function testGetActionReturnsSyncWhenToSyncIsTrue(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setToSync(true);

        $this->assertSame(Combination::ACTION_SYNC, $instance->getAction());
    }

    public function testGetActionReturnsRemoveWhenToRemoveIsTrue(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setToRemove(true);

        $this->assertSame(Combination::ACTION_REMOVE, $instance->getAction());
    }

    public function testGetActionPrioritizesRemoveOverSync(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setToSync(true);
        $instance->setToRemove(true);

        $this->assertSame(Combination::ACTION_REMOVE, $instance->getAction());
    }

    public function testContainsReturnsFalseWhenSelfCombinationEmpty(): void
    {
        $self  = new Combination([], 'rector.yml', 'Workflow', 'job');
        $other = new Combination(['php' => '8.3'], 'rector.yml', 'Workflow', 'job');

        $this->assertFalse($self->contains($other));
    }

    public function testContainsReturnsTrueForExactMatch(): void
    {
        $self  = new Combination(['php' => '8.3', 'symfony' => '~7.4'], 'rector.yml', 'Workflow', 'job');
        $other = new Combination(['php' => '8.3', 'symfony' => '~7.4'], 'rector.yml', 'Workflow', 'job');

        $this->assertTrue($self->contains($other));
    }

    public function testContainsReturnsTrueWhenOtherIsSuperset(): void
    {
        $self  = new Combination(['php' => '8.3'], 'rector.yml', 'Workflow', 'job');
        $other = new Combination(['php' => '8.3', 'symfony' => '~7.4'], 'rector.yml', 'Workflow', 'job');

        $this->assertTrue($self->contains($other));
    }

    public function testContainsReturnsFalseWhenKeyMissingInOther(): void
    {
        $self  = new Combination(['php' => '8.3', 'symfony' => '~7.4'], 'rector.yml', 'Workflow', 'job');
        $other = new Combination(['php' => '8.3'], 'rector.yml', 'Workflow', 'job');

        $this->assertFalse($self->contains($other));
    }

    public function testContainsReturnsFalseWhenValueDiffers(): void
    {
        $self  = new Combination(['php' => '8.3'], 'rector.yml', 'Workflow', 'job');
        $other = new Combination(['php' => '8.4'], 'rector.yml', 'Workflow', 'job');

        $this->assertFalse($self->contains($other));
    }

    public function testContainsIgnoresOrderOfKeys(): void
    {
        // Same key/value pairs, different insertion order
        $self  = new Combination(['php' => '8.3', 'symfony' => '~7.4'], 'rector.yml', 'Workflow', 'job');
        $other = new Combination(['symfony' => '~7.4', 'php' => '8.3'], 'rector.yml', 'Workflow', 'job');

        $this->assertTrue($self->contains($other));
    }

    public function testSetIsOptionalEnablesOptionalByDefault(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setIsOptional();

        $this->assertTrue($instance->isOptional());
    }

    public function testSetIsOptionalDisablesOptionalWhenSetToFalse(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setIsOptional(false);

        $this->assertFalse($instance->isOptional());
    }

    public function testSetIsOptionalUpdatesBasedOnRepeatedCalls(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');

        $instance->setIsOptional(true);
        $this->assertTrue($instance->isOptional());

        $instance->setIsOptional(false);
        $this->assertFalse($instance->isOptional());
    }

    public function testIsOptionalDefaultsToFalse(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');

        $this->assertFalse($instance->isOptional());
    }

    public function testIsOptionalReturnsTrueWhenSetToTrue(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setIsOptional(true);

        $this->assertTrue($instance->isOptional());
    }

    public function testIsOptionalReturnsFalseWhenSetToFalse(): void
    {
        $instance = new Combination([], 'workflow.yml', 'Workflow Name', 'test-job');
        $instance->setIsOptional(false);

        $this->assertFalse($instance->isOptional());
    }
}
