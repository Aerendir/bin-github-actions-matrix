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

namespace Aerendir\Bin\GitHubActionsMatrix\Tests\Repo;

use Aerendir\Bin\GitHubActionsMatrix\Repo\Reader;
use Aerendir\Bin\GitHubActionsMatrix\Utils\Shell;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\ExecException;

class ReaderTest extends TestCase
{
    public function testGetUsernameReturnsUsername(): void
    {
        $shell = $this->createMock(Shell::class);
        $shell
            ->expects(self::once())
            ->method('exec')
            ->with('git config user.name')
            ->willReturn("testuser\n");

        $reader = new Reader($shell);
        $this->assertSame('testuser', $reader->getUsername());
    }

    public function testGetUsernameReturnsNullWhenExecFails(): void
    {
        $shell = $this->createMock(Shell::class);
        $shell
            ->method('exec')
            ->with('git config user.name')
            ->willThrowException(new ExecException());

        $reader = new Reader($shell);
        $this->assertNull($reader->getUsername());
    }

    public function testGetRepoNameParsesCorrectlySshWithGitSuffix(): void
    {
        $shell = $this->createMock(Shell::class);
        $shell
            ->method('exec')
            ->with('git remote get-url origin')
            ->willReturn('git@github.com:vendor/project.git');

        $reader = new Reader($shell);
        $this->assertSame('project', $reader->getRepoName());
    }

    public function testGetRepoNameParsesCorrectlySshWithoutGitSuffix(): void
    {
        $shell = $this->createMock(Shell::class);
        $shell
            ->method('exec')
            ->with('git remote get-url origin')
            ->willReturn('git@github.com:vendor/project');

        $reader = new Reader($shell);
        $this->assertSame('project', $reader->getRepoName());
    }

    public function testGetRepoNameParsesCorrectlyHttpsWithGitSuffix(): void
    {
        $shell = $this->createMock(Shell::class);
        $shell
            ->method('exec')
            ->with('git remote get-url origin')
            ->willReturn('https://www.github.com/vendor/project.git');

        $reader = new Reader($shell);
        $this->assertSame('project', $reader->getRepoName());
    }

    public function testGetRepoNameParsesCorrectlyHttpsWithoutGitSuffix(): void
    {
        $shell = $this->createMock(Shell::class);
        $shell
            ->method('exec')
            ->with('git remote get-url origin')
            ->willReturn('https://www.github.com/vendor/project');

        $reader = new Reader($shell);
        $this->assertSame('project', $reader->getRepoName());
    }

    public function testGetRepoNameThrowsOnInvalidUrl(): void
    {
        $this->expectException(\RuntimeException::class);

        $shell = $this->createMock(Shell::class);
        $shell
            ->method('exec')
            ->with('git remote get-url origin')
            ->willReturn('not-a-valid-url');

        $reader = new Reader($shell);
        $reader->getRepoName();
    }

    public function testFilterProtectedBranchesReturnsOnlyProtectedBranches(): void
    {
        $branches = [
            [
                'name'       => 'main',
                'protection' => [
                    'enabled' => true,
                ],
            ],
            [
                'name'       => 'develop',
                'protection' => [
                    'enabled' => false,
                ],
            ],
            [
                'name'       => 'feature-1',
                'protection' => [
                    'enabled' => true,
                ],
            ],
        ];

        $reader = new Reader();
        $result = $reader->filterProtectedBranches($branches);

        $this->assertEquals(['main', 'feature-1'], $result);
    }

    public function testFilterProtectedBranchesReturnsEmptyArrayIfNoProtectedBranches(): void
    {
        $branches = [
            [
                'name'       => 'main',
                'protection' => [
                    'enabled' => false,
                ],
            ],
            [
                'name'       => 'develop',
                'protection' => [
                    'enabled' => false,
                ],
            ],
        ];

        $reader = new Reader();
        $result = $reader->filterProtectedBranches($branches);

        $this->assertEmpty($result);
    }

    public function testFilterProtectedBranchesHandlesEmptyBranchesArray(): void
    {
        $branches = [];

        $reader = new Reader();
        $result = $reader->filterProtectedBranches($branches);

        $this->assertEmpty($result);
    }
}
