<?php

namespace App\Actions\Task;

use App\Enums\TaskStatus;
use App\Events\TaskStatusChanged;
use App\Interfaces\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use App\Models\User;
use App\Services\Task\TaskStatusRules;
use Illuminate\Support\Facades\DB;

/**
 * Drag & drop on the Kanban board: change column and/or position.
 */
class MoveTaskAction
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskStatusRules $statusRules,
    ) {}

    public function handle(Task $task, TaskStatus $to, ?int $position, User $actor): Task
    {
        $from = $task->status;

        DB::transaction(function () use ($task, $from, $to, $position): void {
            $attributes = $this->statusRules->attributesFor($from, $to);

            if ($attributes !== []) {
                $this->tasks->update($task, $attributes);
            }

            $this->tasks->placeInColumn($task, $position);
        });

        if ($from !== $to) {
            TaskStatusChanged::dispatch($task, $from, $to, $actor);
        }

        return $task;
    }
}
