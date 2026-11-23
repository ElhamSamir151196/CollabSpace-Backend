<?php

namespace App\Actions\Team;

use App\Interfaces\Repositories\TeamRepositoryInterface;
use App\Models\Team;

class DeleteTeamAction
{
    public function __construct(private readonly TeamRepositoryInterface $teams) {}

    /**
     * Soft delete: the team disappears from lists, but tasks and chats that
     * belonged to it keep pointing to a real record.
     */
    public function handle(Team $team): void
    {
        $this->teams->delete($team);
    }
}
