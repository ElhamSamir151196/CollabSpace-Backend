<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Reports\ReportFiltersRequest;
use App\Models\User;
use App\Services\Reports\ReportScopeResolver;
use App\Services\Reports\ReportService;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * GET /reports/users/{user} — a person's report (also Profile > Reports with their own id).
 *  - Own report: every project they can see.
 *  - Someone else's: only the projects the viewer may report on.
 */
class UserReportController extends Controller
{
    public function __invoke(
        ReportFiltersRequest $request,
        User $user,
        #[CurrentUser] User $viewer,
        ReportScopeResolver $scopes,
        ReportService $reports,
    ): JsonResponse {
        Gate::authorize('viewUserReport', $user);

        $scope = $viewer->is($user)
            ? $scopes->forDashboard($viewer, $request->projectId(), $request->from(), $request->to())
            : $scopes->forReports($viewer, $request->projectId(), $request->from(), $request->to());

        return ApiResponse::success($reports->user($user, $scope), meta: [
            'period' => ['from' => $scope->from->toDateString(), 'to' => $scope->to->toDateString()],
        ]);
    }
}
