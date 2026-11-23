<?php

namespace App\Actions\Task;

use App\DTOs\Task\CreateTaskData;
use App\Enums\TaskStatus;
use App\Events\TaskAssigned;
use App\Interfaces\Repositories\TaskRepositoryInterface;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates the card at the bottom of its column ("Add Task" in any column).
 */
class CreateTaskAction
{
    public function __construct(private readonly TaskRepositoryInterface $tasks) {}

    public function handle(Project $project, CreateTaskData $data, User $creator): Task
    {
        $task = DB::transaction(function () use ($project, $data, $creator): Task {
            $completed = $data->status === TaskStatus::Completed;

            $task = $this->tasks->create([
                'project_id' => $project->id,
                'team_id' => $data->teamId,
                'title' => $data->title,
                'description' => $data->description,
                'status' => $data->status,
                'priority' => $data->priority,
                'progress' => $completed ? 100 : 0,
                'start_date' => $data->startDate,
                'due_date' => $data->dueDate,
                'reviewer_id' => $data->reviewerId,
                'position' => $this->tasks->nextPosition($project, $data->status),
                'completed_at' => $completed ? now() : null,
                'created_by' => $creator->id,
            ]);

            $this->tasks->syncAssignees($task, $data->assigneeIds);

            return $task;
        });

        if ($data->assigneeIds !== []) {
            TaskAssigned::dispatch($task, $data->assigneeIds, $creator);
        }

        return $task;
    }
}
