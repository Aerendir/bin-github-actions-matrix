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

use Aerendir\Bin\GitHubActionsMatrix\Workflow\NonDerivableContextDetector;
use PHPUnit\Framework\TestCase;

final class NonDerivableContextDetectorTest extends TestCase
{
    public function testReturnsNullForAStaticNonMatrixJob(): void
    {
        $detector = new NonDerivableContextDetector();

        $this->assertNull($detector->detect([
            'runs-on' => 'ubuntu-latest',
        ]));
    }

    public function testReturnsNullForAStaticMatrixJob(): void
    {
        $detector = new NonDerivableContextDetector();

        $this->assertNull($detector->detect([
            'strategy' => [
                'matrix' => [
                    'php' => ['8.3', '8.4'],
                    'os'  => ['ubuntu-latest', 'windows-latest'],
                ],
            ],
        ]));
    }

    public function testReturnsNullForAStaticNameWithoutExpression(): void
    {
        $detector = new NonDerivableContextDetector();

        $this->assertNull($detector->detect([
            'name'    => 'Build the project',
            'runs-on' => 'ubuntu-latest',
        ]));
    }

    public function testDetectsAnInterpolatedJobName(): void
    {
        $detector = new NonDerivableContextDetector();

        $reason = $detector->detect([
            'name'     => 'build ${{ matrix.os }}',
            'strategy' => [
                'matrix' => ['os' => ['ubuntu-latest', 'windows-latest']],
            ],
        ]);

        $this->assertNotNull($reason);
        $this->assertStringContainsString('name', $reason);
    }

    public function testDetectsAFullyDynamicMatrix(): void
    {
        $detector = new NonDerivableContextDetector();

        $reason = $detector->detect([
            'strategy' => [
                'matrix' => '${{ fromJson(needs.setup.outputs.matrix) }}',
            ],
        ]);

        $this->assertNotNull($reason);
        $this->assertStringContainsString('matrix', $reason);
    }

    public function testDetectsAnExpressionInsideAMatrixValue(): void
    {
        $detector = new NonDerivableContextDetector();

        $reason = $detector->detect([
            'strategy' => [
                'matrix' => [
                    'os'  => '${{ fromJson(needs.setup.outputs.os) }}',
                    'php' => ['8.3'],
                ],
            ],
        ]);

        $this->assertNotNull($reason);
    }

    public function testDetectsAnExpressionUsedAsAMatrixKey(): void
    {
        $detector = new NonDerivableContextDetector();

        $reason = $detector->detect([
            'strategy' => [
                'matrix' => [
                    '${{ fromJson(needs.setup.outputs.key) }}' => ['a', 'b'],
                ],
            ],
        ]);

        $this->assertNotNull($reason);
        $this->assertStringContainsString('matrix', $reason);
    }

    public function testDetectsAFromJsonValueWithoutTheExpressionWrapper(): void
    {
        $detector = new NonDerivableContextDetector();

        $reason = $detector->detect([
            'strategy' => [
                'matrix' => [
                    'include' => 'fromJson(something)',
                ],
            ],
        ]);

        $this->assertNotNull($reason);
    }

    public function testReturnsNullWhenStrategyHasNoMatrix(): void
    {
        $detector = new NonDerivableContextDetector();

        $this->assertNull($detector->detect([
            'strategy' => [
                'fail-fast' => false,
            ],
        ]));
    }
}
