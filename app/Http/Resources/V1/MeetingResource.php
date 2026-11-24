<?php

namespace App\Http\Resources\V1;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A meeting card on the calendar / the meeting page.
 *  - "state": upcoming | live | ended | cancelled (computed from the time).
 *  - "my_attendance": the signed-in user's invitation (null if not invited).
 *  - "can": which buttons to show.
 *
 * @mixin Meeting
 */
class MeetingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $mine = $user !== null && $this->relationLoaded('participants')
            ? $this->participants->firstWhere('id', $user->id)
            : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project->id,
                'slug' => $this->project->slug,
                'name' => $this->project->name,
            ]),
            'start_at' => $this->start_at->toIso8601String(),
            'end_at' => $this->end_at->toIso8601String(),
            'date' => $this->start_at->toDateString(),
            'start_time' => $this->start_at->format('H:i'),
            'end_time' => $this->end_at->format('H:i'),
            'duration_minutes' => $this->durationMinutes(),
            'meeting_link' => $this->meeting_link,
            'cover_url' => $this->coverUrl(),
            'status' => $this->status->toArray(),
            'state' => $this->state(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_by' => new UserSummaryResource($this->whenLoaded('creator')),
            'participants' => MeetingParticipantResource::collection($this->whenLoaded('participants')),
            'participants_count' => $this->whenCounted('participants'),
            'transcripts_count' => $this->whenCounted('transcripts'),
            'notes_count' => $this->whenCounted('notes'),
            'my_attendance' => $this->when($this->relationLoaded('participants'), fn () => $mine === null ? null : [
                'status' => $mine->attendance->attendance_status->toArray(),
                'joined_at' => $mine->attendance->joined_at?->toIso8601String(),
            ]),
            'can' => $this->when($user !== null && $this->relationLoaded('project'), fn () => [
                'update' => (bool) $user?->can('update', $this->resource),
                'manage_participants' => (bool) $user?->can('manageParticipants', $this->resource),
                'manage_content' => (bool) $user?->can('manageContent', $this->resource),
                'join' => (bool) $user?->can('join', $this->resource),
                'add_note' => (bool) $user?->can('addNote', $this->resource),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
