<?php

declare(strict_types=1);

namespace PHPMate\Worker\Domain\Job;

use PHPMate\Worker\Domain\Process\ProcessResult;

final class Job
{
    public const STATUS_STARTED = 'in progress';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';


    /**
     * @var ProcessResult[]
     */
    private array $logs = [];

    private string $status;

    private ?int $duration = null; // TODO


    public function __construct(
        private int $timestamp,
    ) {
        $this->status = self::STATUS_STARTED;
    }


    public function addLog(ProcessResult $processResult): void
    {
        $this->logs[] = $processResult;
    }


    public function markAsSucceeded(): void
    {
        $this->status = self::STATUS_SUCCEEDED;
    }


    public function markAsFailed(): void
    {
        $this->status = self::STATUS_FAILED;
    }


    public function getTimestamp(): int
    {
        return $this->timestamp;
    }


    public function getStatus(): string
    {
        return $this->status;
    }


    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_STARTED;
    }


    public function hasSucceeded(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }


    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }


    /**
     * @return ProcessResult[]
     */
    public function getLogs(): array
    {
        return $this->logs;
    }
}
