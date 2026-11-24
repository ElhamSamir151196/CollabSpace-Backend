<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * A user's invitation to a meeting (the meeting_user row).
 * Available as $user->attendance when loaded through Meeting::participants().
 *
 * @property int $id
 * @property int $meeting_id
 * @property int $user_id
 * @property AttendanceStatus $attendance_status
 * @property Carbon|null $responded_at
 * @property Carbon|null $joined_at
 */
class MeetingParticipant extends Pivot
{
    protected $table = 'meeting_user';

    public $incrementing = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_status' => AttendanceStatus::class,
            'responded_at' => 'datetime',
            'joined_at' => 'datetime',
        ];
    }
}
