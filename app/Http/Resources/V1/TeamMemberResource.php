<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * A user as a member of a team: the user card + their role in the team.
 * Expects the user to be loaded through Team::members() (has ->membership).
 *
 * @mixin User
 */
class TeamMemberResource extends UserSummaryResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'position' => $this->membership->position,
            'is_leader' => $this->membership->is_leader,
            'joined_at' => $this->membership->created_at?->toIso8601String(),
        ];
    }
}
