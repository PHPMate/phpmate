<?php

declare(strict_types=1);

namespace PHPMate\UseCase;

use PHPMate\Domain\Task\Task;
use PHPMate\Domain\Task\TasksCollection;

final class DefineTaskUseCase
{
    public function __construct(
        private TasksCollection $tasks
    ) {}


    public function __invoke(DefineTaskCommand $command): void
    {
        $taskId = $this->tasks->nextIdentity();

        $task = new Task(
            $taskId,
            $command->projectId,
            $command->name,
            $command->commands
        );

        $this->tasks->save($task);
    }
}
