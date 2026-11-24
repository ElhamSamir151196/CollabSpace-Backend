<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AttachmentResource;
use App\Http\Resources\V1\MeetingResource;
use App\Http\Resources\V1\TaskResource;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Services\Reports\DashboardService;
use App\Services\Reports\ReportScopeResolver;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /dashboard?project_id=&year= — every widget of the Dashboard in one call.
 * project_id ("All Projects" dropdown) limits the cards, chart and lists to one project.
 */
class ShowDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        #[CurrentUser] User $user,
        ReportScopeResolver $scopes,
        DashboardService $dashboard,
    ): JsonResponse {
        $request->validate([
            'project_id' => ['nullable', 'integer'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
        ]);

        $scope = $scopes->forDashboard($user, $request->filled('project_id') ? $request->integer('project_id') : null);

        return ApiResponse::success([
            'task_stats' => $dashboard->taskStats($scope),
            'project_overview' => $dashboard->monthlyChart($scope, $request->integer('year', now()->year)),
            'active_projects' => $dashboard->activeProjects($scope)->map(fn (Project $project) => [
                'id' => $project->id,
                'slug' => $project->slug,
                'name' => $project->name,
                'tasks_total' => (int) $project->tasks_count,
                'tasks_completed' => (int) $project->completed_tasks_count,
                'progress' => $project->progressPercent(),
                'deadline' => $project->deadline->toDateString(),
            ])->all(),
            'my_tasks' => TaskResource::collection($dashboard->myTasks($user, $scope))->resolve($request),
            'teams' => $dashboard->teams($user)->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'color' => $team->color,
                'members_count' => (int) $team->members_count,
                'active_projects_count' => (int) $team->getAttribute('active_projects_count'),
            ])->all(),
            'recent_files' => AttachmentResource::collection($dashboard->recentFiles($user))->resolve($request),
            'upcoming_meetings' => MeetingResource::collection($dashboard->upcomingMeetings($user))->resolve($request),
        ]);
    }
}
