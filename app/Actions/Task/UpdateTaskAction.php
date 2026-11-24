<?php

namespace App\Actions\Task;

use App\DTOs\Task\UpdateTaskData;
use App\Events\TaskAssigned;
use App\Events\TaskStatusChanged;
use App\Interfaces\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use App\Models\User;
use App\Services\Task\TaskStatusRules;
use Illuminate\Support\Facades\DB;

/**
 * Full edit by the project's manager (details, status, assignees).
 * A status change here puts the card at the bottom of its new column.
 */
class UpdateTaskAction
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskStatusRules $statusRules,
    ) {}

    public function handle(Task $task, UpdateTaskData $data, User $actor): Task
    {
        $from = $task->status;
        $to = $data->status ?? $from;

        $newlyAssigned = DB::transaction(function () use ($task, $data, $from, $to): array {
            $attributes = [...$data->attributes, ...$this->statusRules->attributesFor($from, $to)];

            // A new due date gets its own "due tomorrow" reminder.
            if (array_key_exists('due_date', $attributes)) {
                $attributes['due_reminder_sent_at'] = null;
            }

            if ($attributes !== []) {
                $this->tasks->update($task, $attributes);
            }

            if ($from !== $to) {
                $this->tasks->placeInColumn($task);
            }

            return $data->assigneeIds !== null
                ? $this->tasks->syncAssignees($task, $data->assigneeIds)
                : [];
        });

        if ($from !== $to) {
            TaskStatusChanged::dispatch($task, $from, $to, $actor);
        }

        if ($newlyAssigned !== []) {
            TaskAssigned::dispatch($task, $newlyAssigned, $actor);
        }

        return $task;
    }
}
