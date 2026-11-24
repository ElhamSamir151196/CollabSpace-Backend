<?php

namespace App\Listeners\Notifications;

use App\Events\TaskAssigned;
use App\Events\TaskStatusChanged;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskStatusChangedNotification;
use App\Services\Notifications\Recipients;
use Illuminate\Support\Facades\Notification;

/**
 * - New assignees: "X assigned you a task".
 * - Status change: the assignees and the reviewer (e.g. the reviewer learns
 *   a card reached "In Review"; assignees learn it was completed).
 */
class NotifyTaskPeople
{
    public function __construct(private readonly Recipients $recipients) {}

    public function handleTaskAssigned(TaskAssigned $event): void
    {
        Notification::send(
            $this->recipients->of($event->userIds, except: $event->assignedBy),
            new TaskAssignedNotification($event->task, $event->assignedBy),
        );
    }

    public function handleTaskStatusChanged(TaskStatusChanged $event): void
    {
        $task = $event->task;

        $ids = $task->assignees()->pluck('users.id')
            ->when($task->reviewer_id !== null, fn ($ids) => $ids->push($task->reviewer_id));

        Notification::send(
            $this->recipients->of($ids, except: $event->changedBy),
            new TaskStatusChangedNotification($task, $event->to, $event->changedBy),
        );
    }
}
