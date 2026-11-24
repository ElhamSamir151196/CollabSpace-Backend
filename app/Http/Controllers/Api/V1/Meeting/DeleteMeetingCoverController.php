<?php

namespace App\Http\Controllers\Api\V1\Meeting;

use App\Actions\Meeting\UpdateMeetingCoverAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\MeetingResource;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * DELETE /meetings/{meeting}/cover — the organizer only.
 */
class DeleteMeetingCoverController extends Controller
{
    public function __invoke(
        Meeting $meeting,
        UpdateMeetingCoverAction $action,
        MeetingRepositoryInterface $meetings,
    ): JsonResponse {
        Gate::authorize('manageContent', $meeting);

        $meeting = $action->handle($meeting, null);

        return ApiResponse::success(new MeetingResource($meetings->loadDetails($meeting)), __('Cover image removed.'));
    }
}
