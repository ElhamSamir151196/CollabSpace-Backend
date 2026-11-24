<?php

namespace App\Notifications;

use App\Enums\NotificationKind;
use App\Models\Task;

class TaskDueSoonNotification extends AppNotification
{
    public function __construct(public readonly Task $task)
    {
        parent::__construct();
    }

    public function kind(): NotificationKind
    {
        return NotificationKind::TaskDueSoon;
    }

    protected function message(): string
    {
        return __('":task" is due on :date.', [
            'task' => $this->task->title,
            'date' => $this->task->due_date?->toFormattedDateString(),
        ]);
    }

    protected function target(): array
    {
        return ['type' => 'task', 'id' => $this->task->id, 'project_slug' => $this->task->project->slug];
    }
}
