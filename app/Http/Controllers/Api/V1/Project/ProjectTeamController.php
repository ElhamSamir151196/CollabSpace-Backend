<?php

namespace App\Http\Controllers\Api\V1\Project;

use App\Actions\Project\AssignProjectTeamsAction;
use App\Actions\Project\UnassignProjectTeamAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Project\StoreProjectTeamsRequest;
use App\Http\Resources\V1\TeamResource;
use App\Models\Project;
use App\Models\Team;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Project > Teams tab: /projects/{project}/teams/{team}.
 * {team} is resolved through $project->teams() (scoped), so a team that is
 * not assigned to the project returns 404.
 */
class ProjectTeamController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $teams = $project->teams()->with('members')->withCount('members')->get();

        return ApiResponse::success(TeamResource::collection($teams));
    }

    public function store(StoreProjectTeamsRequest $request, Project $project, AssignProjectTeamsAction $action): JsonResponse
    {
        $action->handle($project, $request->teamIds());

        $teams = $project->teams()->with('members')->withCount('members')->get();

        return ApiResponse::success(TeamResource::collection($teams), __('Teams assigned successfully.'));
    }

    public function destroy(Project $project, Team $team, UnassignProjectTeamAction $action): JsonResponse
    {
        Gate::authorize('manageMembers', $project);

        $action->handle($project, $team);

        return ApiResponse::message(__('Team removed from the project.'));
    }
}
