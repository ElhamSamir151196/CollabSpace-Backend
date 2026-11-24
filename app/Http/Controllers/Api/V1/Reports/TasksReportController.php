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
 * GET /reports/tasks — by status / priority, overdue, on-time rate, speed.
 */
class TasksReportController extends Controller
{
    public function __invoke(ReportFiltersRequest $request, #[CurrentUser] User $user, ReportScopeResolver $scopes, ReportService $reports): JsonResponse
    {
        Gate::authorize('viewReports');

        $scope = $scopes->forReports($user, $request->projectId(), $request->from(), $request->to());

        return ApiResponse::success($reports->tasks($scope), meta: [
            'period' => ['from' => $scope->from->toDateString(), 'to' => $scope->to->toDateString()],
        ]);
    }
}
