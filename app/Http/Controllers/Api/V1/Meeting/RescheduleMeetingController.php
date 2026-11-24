<?php

namespace App\Http\Controllers\Api\V1\Meeting;

use App\Actions\Meeting\RescheduleMeetingAction;
use App\DTOs\Meeting\MeetingSchedule;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Meeting\RescheduleMeetingRequest;
use App\Http\Resources\V1\MeetingResource;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * PATCH /meetings/{meeting}/reschedule { date, start_time, end_time | duration_minutes }
 */
class RescheduleMeetingController extends Controller
{
    public function __invoke(
        RescheduleMeetingRequest $request,
        Meeting $meeting,
        #[CurrentUser] User $user,
        RescheduleMeetingAction $action,
        MeetingRepositoryInterface $meetings,
    ): JsonResponse {
        $meeting = $action->handle($meeting, MeetingSchedule::fromRequest($request), $user);

        return ApiResponse::success(new MeetingResource($meetings->loadDetails($meeting)), __('Meeting rescheduled.'));
    }
}
