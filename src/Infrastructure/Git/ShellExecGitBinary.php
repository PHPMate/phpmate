<?php

declare(strict_types=1);

namespace PHPMate\Infrastructure\Git;

use PHPMate\Domain\FileSystem\WorkingDirectory;
use PHPMate\Domain\Git\GitBinary;

class ShellExecGitBinary implements GitBinary
{
    private const BINARY_EXECUTABLE = 'git';

    public function execInWorkingDirectory(WorkingDirectory $workingDirectory, string $command): string
    {
        $command = sprintf(
            'cd %s && %s %s',
            $workingDirectory->getAbsolutePath(),
            self::BINARY_EXECUTABLE,
            $command
        );

        return trim((string) shell_exec($command));
    }
}
