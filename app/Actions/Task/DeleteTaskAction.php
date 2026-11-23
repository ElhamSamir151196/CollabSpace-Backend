<?php

namespace App\Actions\Task;

use App\Interfaces\Repositories\TaskRepositoryInterface;
use App\Models\Task;

class DeleteTaskAction
{
    public function __construct(private readonly TaskRepositoryInterface $tasks) {}

    /**
     * Soft delete. The gap left in the column does not matter: cards are
     * ordered by position, and the next move renumbers the column.
     */
    public function handle(Task $task): void
    {
        $this->tasks->delete($task);
    }
}
