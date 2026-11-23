<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when people are newly assigned to a task.
 * The Notifications module will tell them.
 */
class TaskAssigned
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<int>  $userIds
     */
    public function __construct(
        public readonly Task $task,
        public readonly array $userIds,
        public readonly User $assignedBy,
    ) {}
}
