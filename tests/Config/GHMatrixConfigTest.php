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

    public function testMarkOptionalCombinationAddsValidCombination(): void
    {
        $config       = new GHMatrixConfig();
        $workflowName = 'phpunit';
        $combination  = ['php' => '8.4', 'symfony' => '~7.4'];

        $config->markOptionalCombination($workflowName, $combination);

        $optionalCombinations = $config->getOptionalCombinations($workflowName);
        $this->assertCount(1, $optionalCombinations);
        $this->assertSame($combination, $optionalCombinations[0]);
    }

    public function testMarkOptionalCombinationThrowsExceptionForEmptyCombination(): void
    {
        $config       = new GHMatrixConfig();
        $workflowName = 'phpunit';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The combination cannot be empty.');

        $config->markOptionalCombination($workflowName, []);
    }

    public function testMarkOptionalCombinationThrowsExceptionForEmptyWorkflowName(): void
    {
        $config = new GHMatrixConfig();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The workflow name cannot be empty.');

        $config->markOptionalCombination('', ['php' => '8.4']);
    }

    public function testMarkOptionalCombinationSupportsMultipleCombinations(): void
    {
        $config        = new GHMatrixConfig();
        $workflowName  = 'phpunit';
        $combination1  = ['php' => '8.4'];
        $combination2  = ['php' => '8.3', 'symfony' => '~8.0'];

        $config->markOptionalCombination($workflowName, $combination1);
        $config->markOptionalCombination($workflowName, $combination2);

        $optionalCombinations = $config->getOptionalCombinations($workflowName);
        $this->assertCount(2, $optionalCombinations);
        $this->assertSame($combination1, $optionalCombinations[0]);
        $this->assertSame($combination2, $optionalCombinations[1]);
    }

    public function testGetOptionalCombinationsReturnsEmptyArrayWhenNoOptionalCombinations(): void
    {
        $config = new GHMatrixConfig();

        $this->assertSame([], $config->getOptionalCombinations('phpunit'));
    }

    public function testMarkOptionalCombinationSupportsMultipleWorkflows(): void
    {
        $config           = new GHMatrixConfig();
        $phpunitCombo     = ['php' => '8.4'];
        $rectorCombo      = ['php' => '8.3'];

        $config->markOptionalCombination('phpunit', $phpunitCombo);
        $config->markOptionalCombination('rector', $rectorCombo);

        $this->assertSame([$phpunitCombo], $config->getOptionalCombinations('phpunit'));
        $this->assertSame([$rectorCombo], $config->getOptionalCombinations('rector'));
    }

    public function testGetAllOptionalCombinationsReturnsAllWorkflows(): void
    {
        $config       = new GHMatrixConfig();
        $phpunitCombo = ['php' => '8.4'];
        $rectorCombo  = ['php' => '8.3'];

        $config->markOptionalCombination('phpunit', $phpunitCombo);
        $config->markOptionalCombination('rector', $rectorCombo);

        $allOptionalCombinations = $config->getAllOptionalCombinations();

        $this->assertCount(2, $allOptionalCombinations);
        $this->assertArrayHasKey('phpunit', $allOptionalCombinations);
        $this->assertArrayHasKey('rector', $allOptionalCombinations);
        $this->assertSame([$phpunitCombo], $allOptionalCombinations['phpunit']);
        $this->assertSame([$rectorCombo], $allOptionalCombinations['rector']);
    }

    public function testGetAllOptionalCombinationsReturnsEmptyArrayInitially(): void
    {
        $config = new GHMatrixConfig();

        $this->assertSame([], $config->getAllOptionalCombinations());
    }
}
