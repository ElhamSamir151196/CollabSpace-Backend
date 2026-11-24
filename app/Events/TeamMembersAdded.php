<?php

namespace App\Events;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired with the people who were not in the team before.
 */
class TeamMembersAdded
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<int>  $userIds
     */
    public function __construct(
        public readonly Team $team,
        public readonly array $userIds,
        public readonly ?User $by = null,
    ) {}
}
