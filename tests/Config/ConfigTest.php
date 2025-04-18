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

use Aerendir\Bin\GitHubActionsMatrix\Config\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testGetExcludedCombinationsReturnsEmptyArrayInitially(): void
    {
        $config = new Config();
        $this->assertSame([], $config->getExcludedCombinations());
    }

    public function testGetExcludedCombinationsReturnsAddedCombination(): void
    {
        $config              = new Config();
        $excludedCombination = ['key' => 'value'];

        $config->addExcludedCombination($excludedCombination);

        $this->assertSame([$excludedCombination], $config->getExcludedCombinations());
    }

    public function testGetExcludedCombinationsReturnsAllAddedCombinations(): void
    {
        $config               = new Config();
        $excludedCombinations = [
            ['key1' => 'value1'],
            ['key2' => 'value2'],
            ['key3' => 'value3'],
        ];

        foreach ($excludedCombinations as $combination) {
            $config->addExcludedCombination($combination);
        }

        $this->assertSame($excludedCombinations, $config->getExcludedCombinations());
    }

    public function testGetExcludedCombinationsReturnsSetExcludedCombinations(): void
    {
        $config               = new Config();
        $excludedCombinations = [
            ['key1' => 'value1'],
            ['key2' => 'value2'],
        ];

        $config->setExcludedCombination($excludedCombinations);

        $this->assertSame($excludedCombinations, $config->getExcludedCombinations());
    }

    public function testGetExcludedCombinationsOverwritesWithSetExcludedCombinations(): void
    {
        $config = new Config();

        $config->addExcludedCombination(['key1' => 'value1']);
        $config->addExcludedCombination(['key2' => 'value2']);

        $newExcludedCombinations = [
            ['key3' => 'value3'],
            ['key4' => 'value4'],
        ];

        $config->setExcludedCombination($newExcludedCombinations);

        $this->assertSame($newExcludedCombinations, $config->getExcludedCombinations());
    }
}
