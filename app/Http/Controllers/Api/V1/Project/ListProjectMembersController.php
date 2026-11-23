<?php

namespace App\Http\Controllers\Api\V1\Project;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserSummaryResource;
use App\Interfaces\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Everyone in the project (manager + team members + guests), without duplicates.
 * Used for assigning tasks, inviting to meetings and mentions.
 */
class ListProjectMembersController extends Controller
{
    public function __invoke(Project $project, ProjectRepositoryInterface $projects): JsonResponse
    {
        Gate::authorize('view', $project);

        return ApiResponse::success(UserSummaryResource::collection($projects->members($project)));
    }
}
