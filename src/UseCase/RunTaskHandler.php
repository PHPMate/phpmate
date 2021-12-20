<?php

declare(strict_types=1);

namespace PHPMate\UseCase;

use Lcobucci\Clock\Clock;
use PHPMate\Domain\Job\Job;
use PHPMate\Domain\Job\Exception\JobExecutionFailed;
use PHPMate\Domain\Job\Exception\JobHasFinishedAlready;
use PHPMate\Domain\Job\Exception\JobHasNoCommands;
use PHPMate\Domain\Job\Exception\JobHasNotStartedYet;
use PHPMate\Domain\Job\Exception\JobHasStartedAlready;
use PHPMate\Domain\Job\Exception\JobNotFound;
use PHPMate\Domain\Job\JobsCollection;
use PHPMate\Domain\Project\Exception\ProjectNotFound;
use PHPMate\Domain\Project\ProjectsCollection;
use PHPMate\Domain\Task\Exception\TaskNotFound;
use PHPMate\Domain\Task\TasksCollection;
use PHPMate\Packages\MessageBus\Command\CommandBus;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RunTaskHandler implements MessageHandlerInterface
{
    public function __construct(
        private TasksCollection $tasks,
        private JobsCollection $jobs,
        private ProjectsCollection $projects,
        private Clock $clock,
        private CommandBus $commandBus,
    ) {}


    /**
     * @throws TaskNotFound
     * @throws ProjectNotFound
     * @throws JobHasNoCommands
     * @throws JobNotFound
     * @throws JobHasFinishedAlready
     * @throws JobHasStartedAlready
     * @throws JobHasNotStartedYet
     * @throws JobExecutionFailed
     */
    public function __invoke(RunTask $command): void
    {
        $task = $this->tasks->get($command->taskId);
        $project = $this->projects->get($task->projectId);

        $jobId = $this->jobs->nextIdentity();
        $job = Job::scheduleFromTask(
            $jobId,
            $project->projectId,
            $task,
            $this->clock,
        );

        $this->jobs->save($job);

        // TODO: should be event instead, because this is handled asynchronously
        $this->commandBus->dispatch(
            new ExecuteTaskJob($jobId)
        );
    }
}
