<?php

namespace App\Events;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after a project and its teams/guests are saved.
 * Listeners come with later modules:
 *  - Chat: create the project channel + one channel per team.
 *  - Notifications: tell the members they were added.
 */
class ProjectCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Project $project,
        public readonly User $createdBy,
    ) {}
}
