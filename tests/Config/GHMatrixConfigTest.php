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

namespace Aerendir\Bin\GitHubActionsMatrix\Tests\Config;

use Aerendir\Bin\GitHubActionsMatrix\Config\GHMatrixConfig;
use PHPUnit\Framework\TestCase;

class GHMatrixConfigTest extends TestCase
{
    public function testGetUserReturnsNullInitially(): void
    {
        $config = new GHMatrixConfig();
        $this->assertNull($config->getUser());
    }

    public function testSetUserAndGetUser(): void
    {
        $config = new GHMatrixConfig();
        $user   = 'testuser';

        $config->setUser($user);

        $this->assertSame($user, $config->getUser());
    }

    public function testSetUserWithNull(): void
    {
        $config = new GHMatrixConfig();
        $config->setUser('testuser');
        $config->setUser(null);

        $this->assertNull($config->getUser());
    }

    public function testGetBranchReturnsNullInitially(): void
    {
        $config = new GHMatrixConfig();
        $this->assertNull($config->getBranch());
    }

    public function testSetBranchAndGetBranch(): void
    {
        $config = new GHMatrixConfig();
        $branch = 'main';

        $config->setBranch($branch);

        $this->assertSame($branch, $config->getBranch());
    }

    public function testSetBranchWithNull(): void
    {
        $config = new GHMatrixConfig();
        $config->setBranch('main');
        $config->setBranch(null);

        $this->assertNull($config->getBranch());
    }

    public function testGetTokenFileReturnsNullInitially(): void
    {
        $config = new GHMatrixConfig();
        $this->assertNull($config->getTokenFile());
    }

    public function testSetTokenFileAndGetTokenFile(): void
    {
        $config    = new GHMatrixConfig();
        $tokenFile = 'gh_token';

        $config->setTokenFile($tokenFile);

        $this->assertSame($tokenFile, $config->getTokenFile());
    }

    public function testSetTokenFileWithNull(): void
    {
        $config = new GHMatrixConfig();
        $config->setTokenFile('gh_token');
        $config->setTokenFile(null);

        $this->assertNull($config->getTokenFile());
    }
}
