<?php

namespace App\Services\Task;

use App\Enums\TaskStatus;

/**
 * What else changes when a task changes status:
 *  - Completed      -> progress 100, completed_at = now
 *  - Leaving Completed -> completed_at cleared
 */
class TaskStatusRules
{
    /**
     * @return array<string, mixed>
     */
    public function attributesFor(TaskStatus $from, TaskStatus $to): array
    {
        if ($from === $to) {
            return [];
        }

        if ($to === TaskStatus::Completed) {
            return ['status' => $to, 'progress' => 100, 'completed_at' => now()];
        }

        return ['status' => $to, 'completed_at' => null];
    }
}
