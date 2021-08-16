<?php
declare(strict_types=1);

namespace PHPMate\Tests\Unit\UseCase;

use PHPMate\Domain\Project\ProjectId;
use PHPMate\Domain\Task\Task;
use PHPMate\Domain\Task\TaskId;
use PHPMate\Domain\Task\TaskNotFound;
use PHPMate\Infrastructure\Memory\InMemoryTasksCollection;
use PHPMate\UseCase\RedefineTask;
use PHPMate\UseCase\RedefineTaskUseCase;
use PHPUnit\Framework\TestCase;

final class RedefineTaskUseCaseTest extends TestCase
{
    public function testTaskCanBeRedefined(): void
    {
        $tasksCollection = new InMemoryTasksCollection();
        $taskId = new TaskId('1');
        $tasksCollection->save(
            new Task($taskId, new ProjectId(''), 'Task', [])
        );

        $handler = new RedefineTaskUseCase($tasksCollection);
        $handler->handle(
            new RedefineTask(
                $taskId,
                'New name',
                []
            )
        );

        $task = $tasksCollection->get($taskId);

        self::assertSame('New name', $task->name);
    }


    public function testNonExistingTaskCanNotBeRedefined(): void
    {
        $this->expectException(TaskNotFound::class);

        $tasksCollection = new InMemoryTasksCollection();

        $handler = new RedefineTaskUseCase($tasksCollection);
        $handler->handle(
            new RedefineTask(
                new TaskId(''),
                'Name',
                []
            )
        );
    }
}