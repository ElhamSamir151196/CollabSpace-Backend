<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

/**
 * - Project members see the project's tasks.
 * - The project's manager (and admins) create, edit and delete tasks.
 * - Assignees move their cards between To-do / In Progress / In Review
 *   and update progress.
 * - Only the reviewer or a manager can complete a task or reopen a completed one.
 */
class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->can('view', $task->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->manages($user, $project);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->manages($user, $task->project);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->manages($user, $task->project);
    }

    /**
     * Drag the card to another column / position.
     */
    public function move(User $user, Task $task, ?TaskStatus $to = null): bool
    {
        $touchesCompleted = $to === TaskStatus::Completed || $task->status === TaskStatus::Completed;

        if ($touchesCompleted) {
            return $this->complete($user, $task);
        }

        return $this->manages($user, $task->project)
            || $task->isAssignedTo($user)
            || $task->isReviewedBy($user);
    }

    public function updateProgress(User $user, Task $task): bool
    {
        return $this->manages($user, $task->project)
            || $task->isAssignedTo($user)
            || $task->isReviewedBy($user);
    }

    public function complete(User $user, Task $task): bool
    {
        return $this->manages($user, $task->project) || $task->isReviewedBy($user);
    }

    private function manages(User $user, Project $project): bool
    {
        return $user->can(Permission::TasksManage->value) && $user->can('update', $project);
    }
}
