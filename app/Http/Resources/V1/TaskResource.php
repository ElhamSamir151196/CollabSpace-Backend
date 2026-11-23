<?php

namespace App\Http\Resources\V1;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A Kanban card / task details.
 * "can" tells the frontend what the current user may do with the card.
 *
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->toArray(),
            'priority' => $this->priority->toArray(),
            'progress' => $this->progress,
            'position' => $this->position,
            'start_date' => $this->start_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'is_overdue' => $this->isOverdue(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project->id,
                'slug' => $this->project->slug,
                'name' => $this->project->name,
            ]),
            'team' => $this->whenLoaded('team', fn () => [
                'id' => $this->team->id,
                'name' => $this->team->name,
                'color' => $this->team->color,
            ]),
            'reviewer' => new UserSummaryResource($this->whenLoaded('reviewer')),
            'assignees' => UserSummaryResource::collection($this->whenLoaded('assignees')),
            // The paperclip number on the card.
            'attachments_count' => $this->whenCounted('attachments'),
            'created_by' => new UserSummaryResource($this->whenLoaded('creator')),
            'can' => $this->when($this->relationLoaded('project') && $user !== null, fn () => [
                'update' => (bool) $user?->can('update', $this->resource),
                'delete' => (bool) $user?->can('delete', $this->resource),
                'move' => (bool) $user?->can('move', $this->resource),
                'complete' => (bool) $user?->can('complete', $this->resource),
                'update_progress' => (bool) $user?->can('updateProgress', $this->resource),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
