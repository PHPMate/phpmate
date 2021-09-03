<?php

declare(strict_types=1);

namespace PHPMate\Domain\Job;

use JetBrains\PhpStorm\Immutable;
use Lcobucci\Clock\Clock;
use PHPMate\Domain\Process\JobProcess;
use PHPMate\Domain\Process\ProcessResult;
use PHPMate\Domain\Project\ProjectId;
use PHPMate\Domain\Task\TaskId;

#[Immutable(Immutable::PRIVATE_WRITE_SCOPE)]
final class Job
{
    public string $status = JobStatus::SCHEDULED;

    public \DateTimeInterface $scheduledAt;

    private ?\DateTimeInterface $startedAt = null;

    private ?\DateTimeInterface $succeededAt = null;

    private ?\DateTimeInterface $failedAt = null;

    /**
     * @var array<JobProcess>
     */
    public array $processes = [];

    /**
     * @param array<string> $commands
     * @throws JobHasNoCommands
     */
    public function __construct(
        public JobId $jobId,
        public ProjectId $projectId,
        public TaskId $taskId,
        public string $taskName,
        Clock $clock,
        public array $commands
    ) {
        $this->checkThereAreSomeCommands($commands);
        $this->scheduledAt = $clock->now();
    }


    /**
     * @throws JobHasStartedAlready
     */
    public function start(Clock $clock): void
    {
        if ($this->startedAt !== null) {
            throw new JobHasStartedAlready();
        }

        $this->startedAt = $clock->now();
        $this->status = JobStatus::IN_PROGRESS;
    }


    /**
     * @throws JobHasNotStarted
     */
    public function succeeds(Clock $clock): void
    {
        $this->succeededAt = $clock->now();
        $this->status = JobStatus::SUCCEEDED;
    }


    /**
     * @throws JobHasNotStarted
     */
    public function fails(Clock $clock): void
    {
        $this->failedAt = $clock->now();
        $this->status = JobStatus::FAILED;
    }


    public function isInProgress(): bool
    {
        return $this->status === JobStatus::IN_PROGRESS;
    }


    public function hasSucceeded(): bool
    {
        return $this->status === JobStatus::SUCCEEDED;
    }


    public function hasFailed(): bool
    {
        return $this->status === JobStatus::FAILED;
    }


    public function addProcessResult(ProcessResult $processResult): void
    {
        $this->processes[] = new JobProcess(
            $this,
            count($this->processes),
            $processResult
        );
    }


    /**
     * @param array<string> $commands
     * @throws JobHasNoCommands
     */
    private function checkThereAreSomeCommands(array $commands): void
    {
        if (count($commands) <= 0) {
            throw new JobHasNoCommands();
        }
    }
}
