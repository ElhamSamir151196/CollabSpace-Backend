<?php

namespace App\Services\Chat;

use App\Enums\ConversationType;
use App\Models\Conversation;
use App\Models\Project;

/**
 * Makes sure a project has its chat channels:
 *  - "Public Channel" for everyone in the project,
 *  - "<Team> Team Channel" for each team assigned to it.
 * Safe to call many times; it only creates what is missing.
 */
class ProjectChannels
{
    public function ensureFor(Project $project): void
    {
        Conversation::query()->firstOrCreate(
            ['type' => ConversationType::Project->value, 'project_id' => $project->id, 'team_id' => null],
            ['name' => 'Public Channel', 'created_by' => $project->created_by],
        );

        foreach ($project->teams()->get() as $team) {
            Conversation::query()->firstOrCreate(
                ['type' => ConversationType::Team->value, 'project_id' => $project->id, 'team_id' => $team->id],
                ['name' => "{$team->name} Team Channel", 'created_by' => $project->created_by],
            );
        }
    }
}
