<?php

declare(strict_types=1);

namespace PHPMate\Ui\ReadModel\ProjectDetail;

use JetBrains\PhpStorm\Immutable;
use Nette\Utils\Json;
use PHPMate\Domain\Job\JobStatus;

#[Immutable]
final class ReadTask
{
    public string $lastJobStatus = JobStatus::SCHEDULED;


    public function __construct(
        public string $taskId,
        public string $name,
        public ?string $schedule,
        public string $commands,
        public ?string $lastJobId,
        public \DateTimeImmutable $lastJobScheduledAt,
        public ?\DateTimeImmutable $lastJobStartedAt,
        public ?\DateTimeImmutable $lastJobSucceededAt,
        public ?\DateTimeImmutable $lastJobFailedAt,
    ) {
        if ($lastJobFailedAt !== null) {
            $this->lastJobStatus = JobStatus::FAILED;
        } elseif ($lastJobSucceededAt !== null) {
            $this->lastJobStatus = JobStatus::SUCCEEDED;
        } elseif ($lastJobStartedAt !== null) {
            $this->lastJobStatus = JobStatus::IN_PROGRESS;
        }
    }


    public function getCommandsWithNewLines(): string
    {
        $commandsArray = Json::decode($this->commands);

        return implode('"\n', $commandsArray);
    }


    public function getLastJobActionTime(): \DateTimeImmutable
    {
        return $this->lastJobFailedAt
            ?? $this->lastJobSucceededAt
            ?? $this->lastJobStartedAt
            ?? $this->lastJobScheduledAt;
    }


    public function isJobPending(): bool
    {
        return $this->lastJobStatus === JobStatus::SCHEDULED;
    }


    public function isJobInProgress(): bool
    {
        return $this->lastJobStatus === JobStatus::IN_PROGRESS;
    }


    public function hasJobSucceeded(): bool
    {
        return $this->lastJobStatus === JobStatus::SUCCEEDED;
    }


    public function hasJobFailed(): bool
    {
        return $this->lastJobStatus === JobStatus::FAILED;
    }
}