<?php

namespace App\Http\Controllers\Api\V1\Meeting;

use App\Actions\Meeting\CancelMeetingAction;
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
 * PATCH /meetings/{meeting}/cancel
 */
class CancelMeetingController extends Controller
{
    public function __invoke(
        Meeting $meeting,
        #[CurrentUser] User $user,
        CancelMeetingAction $action,
        MeetingRepositoryInterface $meetings,
    ): JsonResponse {
        Gate::authorize('update', $meeting);

        $meeting = $action->handle($meeting, $user);

        return ApiResponse::success(new MeetingResource($meetings->loadDetails($meeting)), __('Meeting cancelled.'));
    }
}
