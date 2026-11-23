<?php

namespace App\Http\Controllers\Api\V1\Project;

use App\Actions\Project\AddProjectGuestsAction;
use App\Actions\Project\RemoveProjectGuestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Project\StoreProjectGuestsRequest;
use App\Http\Resources\V1\UserSummaryResource;
use App\Models\Project;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * People added to a project directly: /projects/{project}/guests/{guest}.
 * {guest} is resolved through $project->guests() (scoped).
 */
class ProjectGuestController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return ApiResponse::success(UserSummaryResource::collection($project->guests()->get()));
    }

    public function store(
        StoreProjectGuestsRequest $request,
        Project $project,
        #[CurrentUser] User $user,
        AddProjectGuestsAction $action,
    ): JsonResponse {
        $action->handle($project, $request->guestIds(), $user);

        return ApiResponse::success(
            UserSummaryResource::collection($project->guests()->get()),
            __('Guests added successfully.'),
        );
    }

    public function destroy(Project $project, User $guest, RemoveProjectGuestAction $action): JsonResponse
    {
        Gate::authorize('manageMembers', $project);

        $action->handle($project, $guest);

        return ApiResponse::message(__('Guest removed from the project.'));
    }
}
