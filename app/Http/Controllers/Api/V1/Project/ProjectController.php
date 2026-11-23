<?php

namespace App\Http\Controllers\Api\V1\Project;

use App\Actions\Project\CreateProjectAction;
use App\Actions\Project\DeleteProjectAction;
use App\Actions\Project\UpdateProjectAction;
use App\DTOs\Project\CreateProjectData;
use App\DTOs\Project\UpdateProjectData;
use App\Filters\ProjectFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Project\IndexProjectsRequest;
use App\Http\Requests\V1\Project\StoreProjectRequest;
use App\Http\Requests\V1\Project\UpdateProjectRequest;
use App\Http\Resources\V1\ProjectResource;
use App\Interfaces\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Projects CRUD. Route key is the slug: /projects/alpha.
 */
class ProjectController extends Controller
{
    public function __construct(private readonly ProjectRepositoryInterface $projects) {}

    public function index(IndexProjectsRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $projects = $this->projects->paginateVisibleTo(
            $user,
            new ProjectFilter($request->filters()),
            $request->integer('per_page', 15),
        );

        return ApiResponse::success(ProjectResource::collection($projects));
    }

    public function store(StoreProjectRequest $request, #[CurrentUser] User $user, CreateProjectAction $action): JsonResponse
    {
        $project = $action->handle(CreateProjectData::fromRequest($request), $user);

        return ApiResponse::created(
            new ProjectResource($this->projects->loadDetails($project)),
            __('Project created successfully.'),
        );
    }

    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return ApiResponse::success(new ProjectResource($this->projects->loadDetails($project)));
    }

    public function update(UpdateProjectRequest $request, Project $project, UpdateProjectAction $action): JsonResponse
    {
        $project = $action->handle($project, UpdateProjectData::fromRequest($request));

        return ApiResponse::success(
            new ProjectResource($this->projects->loadDetails($project)),
            __('Project updated successfully.'),
        );
    }

    public function destroy(Project $project, DeleteProjectAction $action): JsonResponse
    {
        Gate::authorize('delete', $project);

        $action->handle($project);

        return ApiResponse::message(__('Project deleted successfully.'));
    }
}
