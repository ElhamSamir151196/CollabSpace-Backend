<?php

namespace App\Http\Controllers\Api\V1\Meeting;

use App\Actions\Meeting\JoinMeetingAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\MeetingResource;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * POST /meetings/{meeting}/join — records attendance; the frontend then opens data.meeting_link.
 */
class JoinMeetingController extends Controller
{
    public function __invoke(
        Meeting $meeting,
        #[CurrentUser] User $user,
        JoinMeetingAction $action,
        MeetingRepositoryInterface $meetings,
    ): JsonResponse {
        Gate::authorize('join', $meeting);

        $meeting = $action->handle($meeting, $user);

        return ApiResponse::success(new MeetingResource($meetings->loadDetails($meeting)), __('Opening the meeting.'));
    }
}
