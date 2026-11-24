<?php

namespace App\Events;

use App\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after teams are added to an existing project.
 */
class ProjectTeamsAssigned
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<int>  $teamIds
     */
    public function __construct(
        public readonly Project $project,
        public readonly array $teamIds,
    ) {}
}
