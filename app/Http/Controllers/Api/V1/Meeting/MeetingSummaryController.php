<?php

namespace App\Http\Controllers\Api\V1\Meeting;

use App\Actions\Ai\GenerateMeetingSummaryAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\MeetingSummaryResource;
use App\Models\Meeting;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * The "AI Summary" tab: /meetings/{meeting}/summary.
 *  - show:  everyone who can see the meeting (null when not generated yet).
 *  - store: generate / regenerate — the organizer or the project's managers.
 */
class MeetingSummaryController extends Controller
{
    public function show(Meeting $meeting): JsonResponse
    {
        Gate::authorize('view', $meeting);

        $summary = $meeting->summary()->with('generator')->first();

        return ApiResponse::success($summary !== null ? new MeetingSummaryResource($summary) : null);
    }

    public function store(Meeting $meeting, #[CurrentUser] User $user, GenerateMeetingSummaryAction $action): JsonResponse
    {
        Gate::authorize('generateSummary', $meeting);

        $summary = $action->handle($meeting, $user);

        return ApiResponse::created(new MeetingSummaryResource($summary->load('generator')), __('Summary generated.'));
    }
}
