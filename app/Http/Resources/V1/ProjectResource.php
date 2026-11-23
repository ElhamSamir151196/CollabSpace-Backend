<?php

namespace App\Http\Resources\V1;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * List: main fields + manager + teams (id, name, color) + counts.
 * Details: + description, guests, creator.
 * "can" tells the frontend which buttons to show for the current user.
 *
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status->toArray(),
            'priority' => $this->priority->toArray(),
            'start_date' => $this->start_date->toDateString(),
            'deadline' => $this->deadline->toDateString(),
            'is_overdue' => $this->isOverdue(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'manager' => new UserSummaryResource($this->whenLoaded('manager')),
            'teams' => $this->whenLoaded('teams', fn () => $this->teams->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'color' => $team->color,
                'members_count' => $team->members_count,
            ])->all()),
            'guests' => UserSummaryResource::collection($this->whenLoaded('guests')),
            'teams_count' => $this->whenCounted('teams'),
            'guests_count' => $this->whenCounted('guests'),
            'created_by' => new UserSummaryResource($this->whenLoaded('creator')),
            'can' => [
                'update' => (bool) $user?->can('update', $this->resource),
                'delete' => (bool) $user?->can('delete', $this->resource),
                'manage_members' => (bool) $user?->can('manageMembers', $this->resource),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
