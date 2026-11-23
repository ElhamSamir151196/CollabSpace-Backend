<?php

namespace App\Actions\Task;

use App\Interfaces\Repositories\TaskRepositoryInterface;
use App\Models\Task;

/**
 * The progress bar on "In Progress" cards (0-100).
 * Reaching 100% does not complete the task: it still goes through review.
 */
class UpdateTaskProgressAction
{
    public function __construct(private readonly TaskRepositoryInterface $tasks) {}

    public function handle(Task $task, int $progress): Task
    {
        return $this->tasks->update($task, ['progress' => $progress]);
    }
}
