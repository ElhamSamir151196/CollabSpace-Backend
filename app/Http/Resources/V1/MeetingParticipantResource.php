<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * A participant: the user card + their answer and attendance.
 * Expects the user to be loaded through Meeting::participants() (has ->attendance).
 *
 * @mixin User
 */
class MeetingParticipantResource extends UserSummaryResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'attendance_status' => $this->attendance->attendance_status->toArray(),
            'responded_at' => $this->attendance->responded_at?->toIso8601String(),
            'joined_at' => $this->attendance->joined_at?->toIso8601String(),
        ];
    }
}
