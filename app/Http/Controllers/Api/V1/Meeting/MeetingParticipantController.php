<?php

namespace App\Http\Controllers\Api\V1\Meeting;

use App\Actions\Meeting\AddMeetingParticipantsAction;
use App\Actions\Meeting\RemoveMeetingParticipantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Meeting\StoreMeetingParticipantsRequest;
use App\Http\Resources\V1\MeetingParticipantResource;
use App\Models\Meeting;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * /meetings/{meeting}/participants/{participant}
 * {participant} is resolved through $meeting->participants() (scoped).
 */
class MeetingParticipantController extends Controller
{
    public function index(Meeting $meeting): JsonResponse
    {
        Gate::authorize('view', $meeting);

        return ApiResponse::success(MeetingParticipantResource::collection($meeting->participants()->get()));
    }

    public function store(
        StoreMeetingParticipantsRequest $request,
        Meeting $meeting,
        #[CurrentUser] User $user,
        AddMeetingParticipantsAction $action,
    ): JsonResponse {
        $action->handle($meeting, $request->userIds(), $user);

        return ApiResponse::success(
            MeetingParticipantResource::collection($meeting->participants()->get()),
            __('Participants invited.'),
        );
    }

    public function destroy(Meeting $meeting, User $participant, RemoveMeetingParticipantAction $action): JsonResponse
    {
        Gate::authorize('manageParticipants', $meeting);

        $action->handle($meeting, $participant);

        return ApiResponse::message(__('Participant removed.'));
    }
}
