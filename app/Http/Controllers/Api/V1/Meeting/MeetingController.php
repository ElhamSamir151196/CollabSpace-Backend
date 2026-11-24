<?php

namespace App\Http\Controllers\Api\V1\Meeting;

use App\Actions\Meeting\CreateMeetingAction;
use App\Actions\Meeting\DeleteMeetingAction;
use App\Actions\Meeting\UpdateMeetingAction;
use App\DTOs\Meeting\CreateMeetingData;
use App\Filters\MeetingFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Meeting\IndexMeetingsRequest;
use App\Http\Requests\V1\Meeting\StoreMeetingRequest;
use App\Http\Requests\V1\Meeting\UpdateMeetingRequest;
use App\Http\Resources\V1\MeetingResource;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Meetings calendar and CRUD. Reschedule / cancel / join ... are invokable controllers.
 */
class MeetingController extends Controller
{
    public function __construct(private readonly MeetingRepositoryInterface $meetings) {}

    public function index(IndexMeetingsRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $meetings = $this->meetings->calendarFor(
            $user,
            new MeetingFilter($request->filters()),
            $request->integer('limit', 200),
        );

        return ApiResponse::success(MeetingResource::collection($meetings));
    }

    public function store(StoreMeetingRequest $request, #[CurrentUser] User $user, CreateMeetingAction $action): JsonResponse
    {
        /** @var Project $project */
        $project = $request->project();

        $meeting = $action->handle($project, CreateMeetingData::fromRequest($request), $user);

        return ApiResponse::created(new MeetingResource($this->meetings->loadDetails($meeting)), __('Meeting scheduled successfully.'));
    }

    public function show(Meeting $meeting): JsonResponse
    {
        Gate::authorize('view', $meeting);

        return ApiResponse::success(new MeetingResource($this->meetings->loadDetails($meeting)));
    }

    public function update(UpdateMeetingRequest $request, Meeting $meeting, UpdateMeetingAction $action): JsonResponse
    {
        $meeting = $action->handle($meeting, $request->attributesToUpdate());

        return ApiResponse::success(new MeetingResource($this->meetings->loadDetails($meeting)), __('Meeting updated successfully.'));
    }

    public function destroy(Meeting $meeting, DeleteMeetingAction $action): JsonResponse
    {
        Gate::authorize('delete', $meeting);

        $action->handle($meeting);

        return ApiResponse::message(__('Meeting deleted successfully.'));
    }
}
