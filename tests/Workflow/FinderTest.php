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

use Aerendir\Bin\GitHubActionsMatrix\Workflow\Finder;
use PHPUnit\Framework\TestCase;

use function Safe\file_put_contents;
use function Safe\mkdir;
use function Safe\rmdir;
use function Safe\unlink;

class FinderTest extends TestCase
{
    public function testConstructorThrowsExceptionWhenNoFolderExists(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Impossible to locate the GitHub workflows folder');

        new Finder([__DIR__ . '/non-existing-folder']);
    }

    public function testGetWorkflowsReturnsEmptyIteratorIfNoWorkflowsExist(): void
    {
        $tempDir = sys_get_temp_dir() . '/empty-test-folder';
        mkdir($tempDir);

        try {
            $finder    = new Finder([$tempDir]);
            $workflows = $finder->getWorkflows();

            $this->assertCount(0, iterator_to_array($workflows));
        } finally {
            rmdir($tempDir);
        }
    }

    public function testGetWorkflowsReturnsIteratorWithWorkflowFiles(): void
    {
        $tempDir = sys_get_temp_dir() . '/workflow-test-folder';
        mkdir($tempDir);

        $workflowFile = $tempDir . '/test-workflow.yml';
        file_put_contents($workflowFile, 'name: Test Workflow');

        try {
            $finder    = new Finder([$tempDir]);
            $workflows = iterator_to_array($finder->getWorkflows());

            $this->assertCount(1, $workflows);
            $this->assertEquals('test-workflow.yml', $workflows[$workflowFile]->getFilename());
        } finally {
            unlink($workflowFile);
            rmdir($tempDir);
        }
    }
}
