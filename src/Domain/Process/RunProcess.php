<?php

declare(strict_types=1);

namespace Peon\Domain\Process;

use Peon\Domain\Process\Exception\ProcessFailed;
use Peon\Domain\Process\Value\ProcessResult;

interface RunProcess
{
    /**
     * @throws ProcessFailed
     */
    public function inDirectory(
        string $workingDirectory,
        string $command,
        int $timeoutSeconds,
    ): ProcessResult;
}
