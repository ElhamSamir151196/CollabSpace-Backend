<?php

namespace App\Events;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after people are added to a project directly (guests).
 */
class ProjectGuestsAdded
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<int>  $userIds
     */
    public function __construct(
        public readonly Project $project,
        public readonly array $userIds,
        public readonly User $by,
    ) {}
}
