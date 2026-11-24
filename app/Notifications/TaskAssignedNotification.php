<?php

namespace App\Notifications;

use App\Enums\NotificationKind;
use App\Models\Task;
use App\Models\User;

class TaskAssignedNotification extends AppNotification
{
    public function __construct(
        public readonly Task $task,
        public readonly User $by,
    ) {
        parent::__construct();
    }

    public function kind(): NotificationKind
    {
        return NotificationKind::TaskAssigned;
    }

    protected function message(): string
    {
        return __(':name assigned you ":task" in :project.', [
            'name' => $this->by->name,
            'task' => $this->task->title,
            'project' => $this->task->project->name,
        ]);
    }

    protected function target(): array
    {
        return ['type' => 'task', 'id' => $this->task->id, 'project_slug' => $this->task->project->slug];
    }

    protected function actor(): ?User
    {
        return $this->by;
    }
}
