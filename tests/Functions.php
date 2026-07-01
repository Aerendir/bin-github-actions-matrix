<?php

declare(strict_types=1);

namespace Aerendir\Bin\GitHubActionsMatrix\Tests\Functions;

function chdir(string $directory): void
{
    if (false === \chdir($directory)) {
        throw new \RuntimeException(sprintf('Unable to change directory to "%s".', $directory));
    }
}

function file_put_contents(string $filename, string $data): void
{
    if (false === \file_put_contents($filename, $data)) {
        throw new \RuntimeException(sprintf('Unable to write file "%s".', $filename));
    }
}

function getcwd(): string
{
    $cwd = \getcwd();
    if (false === $cwd) {
        throw new \RuntimeException('Unable to determine the current working directory.');
    }

    return $cwd;
}

function mkdir(string $directory, int $permissions = 0777, bool $recursive = false): void
{
    if (false === \mkdir($directory, $permissions, $recursive) && false === \is_dir($directory)) {
        throw new \RuntimeException(sprintf('Unable to create directory "%s".', $directory));
    }
}

function putenv(string $assignment): void
{
    if (false === \putenv($assignment)) {
        throw new \RuntimeException(sprintf('Unable to update environment variable with assignment "%s".', $assignment));
    }
}

function rmdir(string $directory): void
{
    if (false === \rmdir($directory)) {
        throw new \RuntimeException(sprintf('Unable to remove directory "%s".', $directory));
    }
}

function symlink(string $target, string $link): void
{
    if (false === \symlink($target, $link)) {
        throw new \RuntimeException(sprintf('Unable to create symlink "%s" -> "%s".', $link, $target));
    }
}

function unlink(string $filename): void
{
    if (false === \unlink($filename)) {
        throw new \RuntimeException(sprintf('Unable to remove file "%s".', $filename));
    }
}
