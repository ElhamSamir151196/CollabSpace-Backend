<?php

namespace App\Http\Resources\V1;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * List: id, name, color, members_count, leader.
 * Details: + members and creator.
 *
 * @mixin Team
 */
class TeamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'members_count' => $this->whenCounted('members'),
            'leader' => $this->leader(),
            'members' => TeamMemberResource::collection($this->whenLoaded('members')),
            'created_by' => new UserSummaryResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Taken from "members" on the details screen or "leaders" on lists.
     */
    private function leader(): mixed
    {
        $relation = match (true) {
            $this->relationLoaded('members') => 'members',
            $this->relationLoaded('leaders') => 'leaders',
            default => null,
        };

        if ($relation === null) {
            return $this->whenLoaded('leaders');
        }

        $leader = $this->resource->{$relation}->first(fn ($user) => $user->membership->is_leader);

        return $leader !== null ? new TeamMemberResource($leader) : null;
    }
}
