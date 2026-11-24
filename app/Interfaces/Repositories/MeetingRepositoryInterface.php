<?php

namespace App\Interfaces\Repositories;

use App\Enums\AttendanceStatus;
use App\Filters\QueryFilter;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Meeting>
 */
interface MeetingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Calendar / list of meetings the user can see, ordered by start time.
     *
     * @return Collection<int, Meeting>
     */
    public function calendarFor(User $user, QueryFilter $filter, int $limit = 200): Collection;

    /**
     * Adds participants that are not invited yet.
     *
     * @param  list<int>  $userIds
     * @return list<int> ids that were newly added
     */
    public function addParticipants(Meeting $meeting, array $userIds, AttendanceStatus $status = AttendanceStatus::Invited): array;

    public function removeParticipant(Meeting $meeting, User $user): void;

    /**
     * @param  array<string, mixed>  $attributes  attendance_status / responded_at / joined_at
     */
    public function updateParticipant(Meeting $meeting, User $user, array $attributes): void;

    public function nextTranscriptPosition(Meeting $meeting): int;

    public function loadDetails(Meeting $meeting): Meeting;
}
