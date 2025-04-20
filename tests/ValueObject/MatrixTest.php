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
use Aerendir\Bin\GitHubActionsMatrix\ValueObject\Matrix;
use PHPUnit\Framework\TestCase;

class MatrixTest extends TestCase
{
    public function testConstructWithValidCombinations(): void
    {
        $combinations = [
            'phpcs (8.3)' => new Combination(['php' => '8.3'], 'phpcs.yml', 'Test PHP CS Fixer Workflow', 'phpcs'),
            'rector (~7)' => new Combination(['symfony' => '~7'], 'rector.yml', 'Test Rector Workflow', 'rector'),
        ];

        $matrix = new Matrix($combinations);

        $this->assertInstanceOf(Matrix::class, $matrix);
        $this->assertSame($combinations, $matrix->getCombinations());
    }

    public function testConstructWithNonStringKeyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $combinations = [
            0 => new Combination(['php' => '8.3'], 'rector.yml', 'Test Rector Workflow', 'rector'),
        ];

        new Matrix($combinations);
    }

    public function testConstructWithNonCombinationValueThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $combinations = [
            'combination1' => 'not-a-combination',
        ];

        new Matrix($combinations);
    }

    public function testCreateFromArrayWithEmptyMatrixCreatesAtLeastOneEmptyCombination(): void
    {
        $expectedCombinationKey = 'rector ()';
        $expectedCombination    = new Combination([], 'rector.yml', 'Test Rector Workflow', 'rector');

        $matrix = Matrix::createFromArray([], 'rector.yml', 'Test Rector Workflow', 'rector');

        $this->assertInstanceOf(Matrix::class, $matrix);

        $combinations = $matrix->getCombinations();
        $this->assertCount(1, $combinations);
        $this->assertArrayHasKey($expectedCombinationKey, $combinations);

        $this->assertEquals($expectedCombination, $combinations[$expectedCombinationKey]);
    }

    public function testCreateFromArrayWithSimpleMatrix(): void
    {
        $matrixInput = [
            'php' => [
                '8.3',
                '8.4',
            ],
        ];

        $matrix = Matrix::createFromArray($matrixInput, 'rector.yml', 'Test Rector Workflow', 'rector');

        $this->assertInstanceOf(Matrix::class, $matrix);

        $combinations = $matrix->getCombinations();
        $this->assertCount(2, $combinations);

        $this->assertArrayHasKey('rector (8.3)', $combinations);
        $this->assertArrayHasKey('rector (8.4)', $combinations);

        $this->assertEquals(
            new Combination(['php' => '8.3'], 'rector.yml', 'Test Rector Workflow', 'rector'),
            $combinations['rector (8.3)']
        );

        $this->assertEquals(
            new Combination(['php' => '8.4'], 'rector.yml', 'Test Rector Workflow', 'rector'),
            $combinations['rector (8.4)']
        );
    }

    public function testCreateFromArrayWithComplexMatrix(): void
    {
        $matrixInput = [
            'php' => [
                '8.0',
                '8.1',
            ],
            'node' => [
                '16',
                '18',
            ],
        ];

        $matrix = Matrix::createFromArray($matrixInput, 'rector.yml', 'Test Rector Workflow', 'rector');

        $this->assertInstanceOf(Matrix::class, $matrix);

        $combinations = $matrix->getCombinations();
        $this->assertCount(4, $combinations);

        $this->assertArrayHasKey('rector (8.0, 16)', $combinations);
        $this->assertArrayHasKey('rector (8.0, 18)', $combinations);
        $this->assertArrayHasKey('rector (8.1, 16)', $combinations);
        $this->assertArrayHasKey('rector (8.1, 18)', $combinations);

        $this->assertEquals(
            new Combination(['php' => '8.0', 'node' => '16'], 'rector.yml', 'Test Rector Workflow', 'rector'),
            $combinations['rector (8.0, 16)']
        );

        $this->assertEquals(
            new Combination(['php' => '8.0', 'node' => '18'], 'rector.yml', 'Test Rector Workflow', 'rector'),
            $combinations['rector (8.0, 18)']
        );

        $this->assertEquals(
            new Combination(['php' => '8.1', 'node' => '16'], 'rector.yml', 'Test Rector Workflow', 'rector'),
            $combinations['rector (8.1, 16)']
        );

        $this->assertEquals(
            new Combination(['php' => '8.1', 'node' => '18'], 'rector.yml', 'Test Rector Workflow', 'rector'),
            $combinations['rector (8.1, 18)']
        );
    }

    public function testCreateFromArrayWithInvalidMatrix(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $invalidMatrixInput = [
            'php' => 'not-an-array', // Invalid type.
        ];

        Matrix::createFromArray($invalidMatrixInput, 'rector.yml', 'Test Rector Workflow', 'rector');
    }

    public function testMergeWithNonOverlappingCombinations(): void
    {
        $matrix1Combinations = [
            'phpcs (8.3)' => new Combination(['php' => '8.3'], 'phpcs.yml', 'Test PHP CS Fixer Workflow', 'phpcs'),
        ];
        $matrix2Combinations = [
            'rector (~7)' => new Combination(['symfony' => '~7'], 'workflow2.yml', 'Test Workflow 2', 'rector'),
        ];

        $matrix1 = new Matrix($matrix1Combinations);
        $matrix2 = new Matrix($matrix2Combinations);

        $matrix1->merge($matrix2);

        $this->assertCount(2, $matrix1->getCombinations());
        $this->assertSame($matrix1Combinations['phpcs (8.3)'], $matrix1->getCombinations()['phpcs (8.3)']);
        $this->assertSame($matrix2Combinations['rector (~7)'], $matrix1->getCombinations()['rector (~7)']);
    }

    public function testMergeWithEmptySecondMatrix(): void
    {
        $matrix1Combinations = [
            'phpcs (8.3)' => new Combination(['php' => '8.3'], 'phpcs.yml', 'Test PHP CS Fixer Workflow', 'phpcs'),
        ];

        $matrix1 = new Matrix($matrix1Combinations);
        $matrix2 = new Matrix([]);

        $matrix1->merge($matrix2);

        $this->assertCount(1, $matrix1->getCombinations());
        $this->assertSame($matrix1Combinations['phpcs (8.3)'], $matrix1->getCombinations()['phpcs (8.3)']);
    }

    public function testMergeWithEmptyFirstMatrix(): void
    {
        $matrix2Combinations = [
            'rector (~7)' => new Combination(['symfony' => '~7'], 'workflow2.yml', 'Test Workflow 2', 'rector'),
        ];

        $matrix1 = new Matrix([]);
        $matrix2 = new Matrix($matrix2Combinations);

        $matrix1->merge($matrix2);

        $this->assertCount(1, $matrix1->getCombinations());
        $this->assertSame($matrix2Combinations['rector (~7)'], $matrix1->getCombinations()['rector (~7)']);
    }

    public function testMergeWithBothMatricesEmpty(): void
    {
        $matrix1 = new Matrix([]);
        $matrix2 = new Matrix([]);

        $matrix1->merge($matrix2);

        $this->assertCount(0, $matrix1->getCombinations());
    }
}
