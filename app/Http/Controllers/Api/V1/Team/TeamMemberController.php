<?php

namespace App\Http\Controllers\Api\V1\Team;

use App\Actions\Team\AddTeamMembersAction;
use App\Actions\Team\RemoveTeamMemberAction;
use App\Actions\Team\UpdateTeamMemberAction;
use App\DTOs\Team\UpdateTeamMemberData;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Team\StoreTeamMembersRequest;
use App\Http\Requests\V1\Team\UpdateTeamMemberRequest;
use App\Http\Resources\V1\TeamMemberResource;
use App\Models\Team;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Nested resource: /teams/{team}/members/{member}.
 * {member} is resolved through $team->members(), so a user who is not
 * in the team returns 404 automatically (scoped binding).
 */
class TeamMemberController extends Controller
{
    public function index(Team $team): JsonResponse
    {
        Gate::authorize('view', $team);

        return ApiResponse::success(TeamMemberResource::collection($team->members()->get()));
    }

    public function store(StoreTeamMembersRequest $request, Team $team, #[CurrentUser] User $user, AddTeamMembersAction $action): JsonResponse
    {
        $action->handle($team, $request->members(), $user);

        return ApiResponse::success(
            TeamMemberResource::collection($team->members()->get()),
            __('Members saved successfully.'),
        );
    }

    public function update(UpdateTeamMemberRequest $request, Team $team, User $member, UpdateTeamMemberAction $action): JsonResponse
    {
        $action->handle($team, $member, UpdateTeamMemberData::fromRequest($request));

        /** @var User $fresh */
        $fresh = $team->members()->whereKey($member->id)->firstOrFail();

        return ApiResponse::success(new TeamMemberResource($fresh), __('Member updated successfully.'));
    }

    public function destroy(Team $team, User $member, RemoveTeamMemberAction $action): JsonResponse
    {
        Gate::authorize('manageMembers', $team);

        $action->handle($team, $member);

        return ApiResponse::message(__('Member removed from the team.'));
    }
}
