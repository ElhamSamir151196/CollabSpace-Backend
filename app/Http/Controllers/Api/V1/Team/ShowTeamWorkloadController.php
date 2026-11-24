<?php

namespace App\Http\Controllers\Api\V1\Team;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\Reports\ReportScopeResolver;
use App\Services\Reports\ReportService;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * GET /teams/{team}/workload?project_id= — each member with completed / assigned tasks
 * (the "12/20" bars on the Teams tab and the Dashboard). Counts only cover projects
 * the viewer can see.
 */
class ShowTeamWorkloadController extends Controller
{
    public function __invoke(
        Request $request,
        Team $team,
        #[CurrentUser] User $user,
        ReportScopeResolver $scopes,
        ReportService $reports,
    ): JsonResponse {
        Gate::authorize('view', $team);

        $request->validate(['project_id' => ['nullable', 'integer']]);

        $scope = $scopes->forDashboard($user, $request->filled('project_id') ? $request->integer('project_id') : null);

        return ApiResponse::success($reports->teamWorkload($team, $scope));
    }
}
