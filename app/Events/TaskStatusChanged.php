<?php

namespace App\Events;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a task moves to another Kanban column.
 * Used later for notifications and live board updates.
 */
class TaskStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly TaskStatus $from,
        public readonly TaskStatus $to,
        public readonly User $changedBy,
    ) {}
}
