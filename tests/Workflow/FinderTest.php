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
    public function testGetWorkflowsThrowsExceptionWhenNoFolderExists(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Impossible to locate the GitHub workflows folder');

        // No fallbacks injected, so the only candidate is the (missing) explicit folder.
        (new Finder(fallbackFolders: []))->getWorkflows([__DIR__ . '/non-existing-folder']);
    }

    public function testGetWorkflowsReturnsEmptyIteratorIfNoWorkflowsExist(): void
    {
        $tempDir = sys_get_temp_dir() . '/empty-test-folder';
        mkdir($tempDir);

        try {
            $workflows = (new Finder(fallbackFolders: []))->getWorkflows([$tempDir]);

            $this->assertCount(0, iterator_to_array($workflows));
        } finally {
            rmdir($tempDir);
        }
    }

    public function testGetWorkflowsPicksTheFirstExistingFolderFromTheCandidates(): void
    {
        $missingDir  = sys_get_temp_dir() . '/finder-missing-' . uniqid();
        $existingDir = sys_get_temp_dir() . '/finder-existing-' . uniqid();
        $laterDir    = sys_get_temp_dir() . '/finder-later-' . uniqid();
        mkdir($existingDir);
        mkdir($laterDir);

        // The workflow lives in the FIRST existing folder; the one in $laterDir must be ignored.
        file_put_contents($existingDir . '/winner.yml', 'name: Winner');
        file_put_contents($laterDir . '/loser.yml', 'name: Loser');

        try {
            // Order: missing (skipped) -> existing (picked) -> later (never reached).
            $workflows = iterator_to_array((new Finder(fallbackFolders: []))->getWorkflows([$missingDir, $existingDir, $laterDir]));

            $this->assertCount(1, $workflows);
            $this->assertEquals('winner.yml', $workflows[$existingDir . '/winner.yml']->getFilename());
        } finally {
            unlink($existingDir . '/winner.yml');
            unlink($laterDir . '/loser.yml');
            rmdir($existingDir);
            rmdir($laterDir);
        }
    }

    public function testGetWorkflowsFallsBackToFallbackFoldersWhenNoExplicitFolderMatches(): void
    {
        $missingDir  = sys_get_temp_dir() . '/finder-missing-' . uniqid();
        $fallbackDir = sys_get_temp_dir() . '/finder-fallback-' . uniqid();
        mkdir($fallbackDir);
        file_put_contents($fallbackDir . '/from-fallback.yml', 'name: Fallback');

        try {
            // The explicit folder does not exist, so the injected fallback (tried last) must win.
            $workflows = iterator_to_array((new Finder(fallbackFolders: [$fallbackDir]))->getWorkflows([$missingDir]));

            $this->assertCount(1, $workflows);
            $this->assertEquals('from-fallback.yml', $workflows[$fallbackDir . '/from-fallback.yml']->getFilename());
        } finally {
            unlink($fallbackDir . '/from-fallback.yml');
            rmdir($fallbackDir);
        }
    }

    public function testGetWorkflowsDiscoversBothYmlAndYamlExtensions(): void
    {
        $tempDir = sys_get_temp_dir() . '/finder-mixed-ext-' . uniqid();
        mkdir($tempDir);

        // GitHub Actions recognises both extensions: the Finder must discover them both.
        file_put_contents($tempDir . '/with-yml.yml', 'name: With yml');
        file_put_contents($tempDir . '/with-yaml.yaml', 'name: With yaml');

        try {
            $workflows = iterator_to_array((new Finder(fallbackFolders: []))->getWorkflows([$tempDir]));

            $this->assertCount(2, $workflows);
            $this->assertArrayHasKey($tempDir . '/with-yml.yml', $workflows);
            $this->assertArrayHasKey($tempDir . '/with-yaml.yaml', $workflows);
        } finally {
            unlink($tempDir . '/with-yml.yml');
            unlink($tempDir . '/with-yaml.yaml');
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
            $workflows = iterator_to_array((new Finder(fallbackFolders: []))->getWorkflows([$tempDir]));

            $this->assertCount(1, $workflows);
            $this->assertEquals('test-workflow.yml', $workflows[$workflowFile]->getFilename());
        } finally {
            unlink($workflowFile);
            rmdir($tempDir);
        }
    }
}
