<?php

namespace App\Repositories\Eloquent;

use App\Enums\AttendanceStatus;
use App\Filters\QueryFilter;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Meeting>
 */
class MeetingRepository extends BaseRepository implements MeetingRepositoryInterface
{
    public function __construct(Meeting $model)
    {
        parent::__construct($model);
    }

    public function calendarFor(User $user, QueryFilter $filter, int $limit = 200): Collection
    {
        $query = $this->query()
            ->visibleTo($user)
            ->with(['project', 'participants'])
            ->withCount('participants');

        $filter->apply($query);

        return $query->limit(max(1, min($limit, 500)))->get();
    }

    public function addParticipants(Meeting $meeting, array $userIds, AttendanceStatus $status = AttendanceStatus::Invited): array
    {
        $existing = $meeting->participants()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        $new = array_values(array_diff($userIds, $existing));

        if ($new !== []) {
            $meeting->participants()->attach(array_fill_keys($new, [
                'attendance_status' => $status->value,
                'responded_at' => $status === AttendanceStatus::Invited ? null : now(),
            ]));
        }

        return $new;
    }

    public function removeParticipant(Meeting $meeting, User $user): void
    {
        $meeting->participants()->detach($user->id);
    }

    public function updateParticipant(Meeting $meeting, User $user, array $attributes): void
    {
        $meeting->participants()->updateExistingPivot($user->id, $attributes);
    }

    public function nextTranscriptPosition(Meeting $meeting): int
    {
        $max = $meeting->transcripts()->max('position');

        return $max === null ? 0 : (int) $max + 1;
    }

    public function loadDetails(Meeting $meeting): Meeting
    {
        return $meeting
            ->load(['project', 'creator', 'participants'])
            ->loadCount(['participants', 'transcripts', 'notes']);
    }
}
