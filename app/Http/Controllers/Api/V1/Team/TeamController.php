<?php

namespace App\Http\Controllers\Api\V1\Team;

use App\Actions\Team\CreateTeamAction;
use App\Actions\Team\DeleteTeamAction;
use App\Actions\Team\UpdateTeamAction;
use App\DTOs\Team\CreateTeamData;
use App\DTOs\Team\UpdateTeamData;
use App\Filters\TeamFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Team\IndexTeamsRequest;
use App\Http\Requests\V1\Team\StoreTeamRequest;
use App\Http\Requests\V1\Team\UpdateTeamRequest;
use App\Http\Resources\V1\TeamResource;
use App\Interfaces\Repositories\TeamRepositoryInterface;
use App\Models\Team;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TeamController extends Controller
{
    public function __construct(private readonly TeamRepositoryInterface $teams) {}

    public function index(IndexTeamsRequest $request): JsonResponse
    {
        $teams = $this->teams->paginateForList(
            new TeamFilter($request->filters()),
            $request->integer('per_page', 15),
        );

        return ApiResponse::success(TeamResource::collection($teams));
    }

    public function store(StoreTeamRequest $request, #[CurrentUser] User $user, CreateTeamAction $action): JsonResponse
    {
        $team = $action->handle(CreateTeamData::fromRequest($request), $user);

        return ApiResponse::created(new TeamResource($this->teams->loadDetails($team)), __('Team created successfully.'));
    }

    public function show(Team $team): JsonResponse
    {
        Gate::authorize('view', $team);

        return ApiResponse::success(new TeamResource($this->teams->loadDetails($team)));
    }

    public function update(UpdateTeamRequest $request, Team $team, UpdateTeamAction $action): JsonResponse
    {
        $team = $action->handle($team, UpdateTeamData::fromRequest($request));

        return ApiResponse::success(new TeamResource($this->teams->loadDetails($team)), __('Team updated successfully.'));
    }

    public function destroy(Team $team, DeleteTeamAction $action): JsonResponse
    {
        Gate::authorize('delete', $team);

        $action->handle($team);

        return ApiResponse::message(__('Team deleted successfully.'));
    }
}
