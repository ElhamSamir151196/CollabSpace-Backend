<?php

namespace App\Notifications;

use App\Enums\NotificationKind;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

class TaskStatusChangedNotification extends AppNotification
{
    public function __construct(
        public readonly Task $task,
        public readonly TaskStatus $to,
        public readonly User $by,
    ) {
        parent::__construct();
    }

    public function kind(): NotificationKind
    {
        return NotificationKind::TaskStatusChanged;
    }

    protected function message(): string
    {
        return __(':name moved ":task" to :status.', [
            'name' => $this->by->name,
            'task' => $this->task->title,
            'status' => $this->to->label(),
        ]);
    }

    protected function target(): array
    {
        return [
            'type' => 'task',
            'id' => $this->task->id,
            'project_slug' => $this->task->project->slug,
            'status' => $this->to->value,
        ];
    }

    protected function actor(): ?User
    {
        return $this->by;
    }
}
