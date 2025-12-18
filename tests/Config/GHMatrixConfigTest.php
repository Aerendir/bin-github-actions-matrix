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

    public function testMarkSoftCombinationAddsValidCombination(): void
    {
        $config       = new GHMatrixConfig();
        $workflowName = 'phpunit';
        $combination  = ['php' => '8.4', 'symfony' => '~7.4'];

        $config->markSoftCombination($workflowName, $combination);

        $softCombinations = $config->getSoftCombinations($workflowName);
        $this->assertCount(1, $softCombinations);
        $this->assertSame($combination, $softCombinations[0]);
    }

    public function testMarkSoftCombinationThrowsExceptionForEmptyCombination(): void
    {
        $config       = new GHMatrixConfig();
        $workflowName = 'phpunit';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The combination cannot be empty.');

        $config->markSoftCombination($workflowName, []);
    }

    public function testMarkSoftCombinationSupportsMultipleCombinations(): void
    {
        $config        = new GHMatrixConfig();
        $workflowName  = 'phpunit';
        $combination1  = ['php' => '8.4'];
        $combination2  = ['php' => '8.3', 'symfony' => '~8.0'];

        $config->markSoftCombination($workflowName, $combination1);
        $config->markSoftCombination($workflowName, $combination2);

        $softCombinations = $config->getSoftCombinations($workflowName);
        $this->assertCount(2, $softCombinations);
        $this->assertSame($combination1, $softCombinations[0]);
        $this->assertSame($combination2, $softCombinations[1]);
    }

    public function testGetSoftCombinationsReturnsEmptyArrayWhenNoSoftCombinations(): void
    {
        $config = new GHMatrixConfig();

        $this->assertSame([], $config->getSoftCombinations('phpunit'));
    }

    public function testMarkSoftCombinationSupportsMultipleWorkflows(): void
    {
        $config           = new GHMatrixConfig();
        $phpunitCombo     = ['php' => '8.4'];
        $rectorCombo      = ['php' => '8.3'];

        $config->markSoftCombination('phpunit', $phpunitCombo);
        $config->markSoftCombination('rector', $rectorCombo);

        $this->assertSame([$phpunitCombo], $config->getSoftCombinations('phpunit'));
        $this->assertSame([$rectorCombo], $config->getSoftCombinations('rector'));
    }

    public function testGetAllSoftCombinationsReturnsAllWorkflows(): void
    {
        $config       = new GHMatrixConfig();
        $phpunitCombo = ['php' => '8.4'];
        $rectorCombo  = ['php' => '8.3'];

        $config->markSoftCombination('phpunit', $phpunitCombo);
        $config->markSoftCombination('rector', $rectorCombo);

        $allSoftCombinations = $config->getAllSoftCombinations();

        $this->assertCount(2, $allSoftCombinations);
        $this->assertArrayHasKey('phpunit', $allSoftCombinations);
        $this->assertArrayHasKey('rector', $allSoftCombinations);
        $this->assertSame([$phpunitCombo], $allSoftCombinations['phpunit']);
        $this->assertSame([$rectorCombo], $allSoftCombinations['rector']);
    }

    public function testGetAllSoftCombinationsReturnsEmptyArrayInitially(): void
    {
        $config = new GHMatrixConfig();

        $this->assertSame([], $config->getAllSoftCombinations());
    }
}
