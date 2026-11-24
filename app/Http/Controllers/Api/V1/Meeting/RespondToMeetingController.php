<?php

namespace App\Http\Controllers\Api\V1\Meeting;

use App\Actions\Meeting\RespondToMeetingAction;
use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Meeting\RespondToMeetingRequest;
use App\Http\Resources\V1\MeetingResource;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * PATCH /meetings/{meeting}/attendance { status: accepted | declined }
 */
class RespondToMeetingController extends Controller
{
    public function __invoke(
        RespondToMeetingRequest $request,
        Meeting $meeting,
        #[CurrentUser] User $user,
        RespondToMeetingAction $action,
        MeetingRepositoryInterface $meetings,
    ): JsonResponse {
        $action->handle($meeting, $user, $request->enum('status', AttendanceStatus::class) ?? AttendanceStatus::Accepted);

        return ApiResponse::success(new MeetingResource($meetings->loadDetails($meeting)), __('Your answer was saved.'));
    }
}
