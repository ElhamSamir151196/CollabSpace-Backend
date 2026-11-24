<?php

namespace App\Http\Controllers\Api\V1\Meeting;

use App\Actions\Meeting\UpdateMeetingCoverAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Meeting\UpdateMeetingCoverRequest;
use App\Http\Resources\V1\MeetingResource;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

/**
 * POST /meetings/{meeting}/cover (multipart "cover") — the organizer only.
 */
class UpdateMeetingCoverController extends Controller
{
    public function __invoke(
        UpdateMeetingCoverRequest $request,
        Meeting $meeting,
        UpdateMeetingCoverAction $action,
        MeetingRepositoryInterface $meetings,
    ): JsonResponse {
        /** @var UploadedFile $cover */
        $cover = $request->file('cover');

        $meeting = $action->handle($meeting, $cover);

        return ApiResponse::success(new MeetingResource($meetings->loadDetails($meeting)), __('Cover image updated.'));
    }
}
