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

namespace Aerendir\Bin\GitHubActionsMatrix\ValueObject;

final class Job
{
    public function __construct(private readonly string $name, private readonly Matrix $matrix)
    {
    }

    /**
     * @param array{
     *      strategy: array{
     *          matrix: array{
     *              string: array<string>,
     *          }
     *      }
     *  } $content Array containing the details necessary to create an instance.
     *             The 'strategy' key should contain an array with a 'matrix' key,
     *             which in turn holds the configuration details for the matrix.
     */
    public static function createFromArray(string $name, array $content, string $workflowFilename, string $workflowName, string $job): self
    {
        if (false === array_key_exists('strategy', $content)) {
            throw new \InvalidArgumentException('The "strategy" key is missing in the provided content.');
        }

        if (false === is_array($content['strategy'])) {
            throw new \InvalidArgumentException('The "strategy" key must be an array.');
        }

        if (false === array_key_exists('matrix', $content['strategy'])) {
            throw new \InvalidArgumentException('The "matrix" key is missing in the provided content.');
        }

        if (false === is_array($content['strategy']['matrix'])) {
            throw new \InvalidArgumentException('The "matrix" key must be an array.');
        }

        $matrix = Matrix::createFromArray($content['strategy']['matrix'], $workflowFilename, $workflowName, $job);

        return new Job($name, $matrix);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMatrix(): Matrix
    {
        return $this->matrix;
    }
}
