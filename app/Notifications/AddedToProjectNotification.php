<?php

namespace App\Notifications;

use App\Enums\NotificationKind;
use App\Models\Project;
use App\Models\User;

class AddedToProjectNotification extends AppNotification
{
    public function __construct(
        public readonly Project $project,
        public readonly ?User $by = null,
    ) {
        parent::__construct();
    }

    public function kind(): NotificationKind
    {
        return NotificationKind::AddedToProject;
    }

    protected function message(): string
    {
        return __('You were added to the project ":project".', ['project' => $this->project->name]);
    }

    protected function target(): array
    {
        return ['type' => 'project', 'id' => $this->project->id, 'project_slug' => $this->project->slug];
    }

    protected function actor(): ?User
    {
        return $this->by;
    }
}
