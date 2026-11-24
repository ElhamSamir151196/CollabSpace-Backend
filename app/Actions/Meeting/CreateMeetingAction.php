<?php

namespace App\Actions\Meeting;

use App\DTOs\Meeting\CreateMeetingData;
use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Events\Meeting\MeetingScheduled;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\User;
use App\Services\Meeting\MeetingCoverStorage;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Schedules a meeting. The creator is added as a participant who already
 * accepted; everyone else is "invited" and gets notified.
 */
class CreateMeetingAction
{
    public function __construct(
        private readonly MeetingRepositoryInterface $meetings,
        private readonly MeetingCoverStorage $covers,
    ) {}

    public function handle(Project $project, CreateMeetingData $data, User $creator): Meeting
    {
        $coverPath = $data->cover !== null ? $this->covers->store($data->cover) : null;

        try {
            [$meeting, $invited] = DB::transaction(function () use ($project, $data, $creator, $coverPath): array {
                $meeting = $this->meetings->create([
                    'project_id' => $project->id,
                    'title' => $data->title,
                    'description' => $data->description,
                    'start_at' => $data->schedule->startAt,
                    'end_at' => $data->schedule->endAt,
                    'meeting_link' => $data->meetingLink,
                    'cover_path' => $coverPath,
                    'status' => MeetingStatus::Scheduled,
                    'created_by' => $creator->id,
                ]);

                $this->meetings->addParticipants($meeting, [$creator->id], AttendanceStatus::Accepted);
                $invited = $this->meetings->addParticipants(
                    $meeting,
                    array_values(array_diff($data->participantIds, [$creator->id])),
                );

                return [$meeting, $invited];
            });
        } catch (Throwable $e) {
            $this->covers->delete($coverPath);

            throw $e;
        }

        MeetingScheduled::dispatch($meeting, $invited, $creator);

        return $meeting;
    }
}
