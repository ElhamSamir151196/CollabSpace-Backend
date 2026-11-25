<?php

namespace App\Http\Controllers\Api\V1\Project;

use App\Actions\Ai\GenerateProjectInsightsAction;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * GET /projects/{project}/ai-insights?refresh=1 — "AI Insights" on the project overview.
 */
class ProjectInsightsController extends Controller
{
    public function __invoke(Request $request, Project $project, #[CurrentUser] User $user, GenerateProjectInsightsAction $action): JsonResponse
    {
        Gate::authorize('view', $project);

        return ApiResponse::success($action->handle($project, $user, $request->boolean('refresh')));
    }
}
