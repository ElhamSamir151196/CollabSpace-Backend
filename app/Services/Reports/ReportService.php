<?php

namespace App\Services\Reports;

use App\DTOs\Reports\ReportScope;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Http\Resources\V1\UserSummaryResource;
use App\Interfaces\Repositories\ReportRepositoryInterface;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Support\Metrics;
use Carbon\CarbonImmutable;

/**
 * Numbers for the Reports screen and the report API.
 * Every figure is limited to the projects in the ReportScope.
 */
class ReportService
{
    public function __construct(private readonly ReportRepositoryInterface $reports) {}

    /**
     * The Reports page: three rate cards and three charts.
     *
     * @return array<string, mixed>
     */
    public function overview(ReportScope $scope): array
    {
        $previous = $scope->previous();

        return [
            'task_completion_rate' => Metrics::rateWithChange($this->taskCompletionRate($scope), $this->taskCompletionRate($previous)),
            'project_completion_rate' => Metrics::rateWithChange($this->projectCompletionRate($scope, $scope->to), $this->projectCompletionRate($scope, $previous->to)),
            'team_activity_rate' => Metrics::rateWithChange($this->teamActivityRate($scope), $this->teamActivityRate($previous)),
            'task_time_vs_deadline' => $this->onTimePerMonth($scope),
            'projects_progress' => $this->progressPerMonth($scope),
            'worker_activity' => $this->workerActivityPerMonth($scope),
        ];
    }

    /**
     * Projects report: totals by state + one row per project.
     *
     * @return array<string, mixed>
     */
    public function projects(ReportScope $scope): array
    {
        $rows = $this->reports->projectRows($scope);
        $count = fn (ProjectStatus $status) => $rows->filter(fn (Project $project) => $project->status === $status)->count();

        return [
            'summary' => [
                'total' => $rows->count(),
                'active' => $count(ProjectStatus::InProgress),
                'pending' => $count(ProjectStatus::Pending),
                'on_hold' => $count(ProjectStatus::OnHold),
                'completed' => $count(ProjectStatus::Completed),
                'cancelled' => $count(ProjectStatus::Cancelled),
                'delayed' => $rows->filter(fn (Project $project) => $project->isOverdue())->count(),
                'completion_rate' => Metrics::rate($count(ProjectStatus::Completed), $rows->count()),
            ],
            'projects' => $rows->map(fn (Project $project) => [
                'id' => $project->id,
                'slug' => $project->slug,
                'name' => $project->name,
                'status' => $project->status->toArray(),
                'priority' => $project->priority->toArray(),
                'deadline' => $project->deadline->toDateString(),
                'is_overdue' => $project->isOverdue(),
                'progress' => $project->progressPercent(),
                'tasks_total' => (int) $project->tasks_count,
                'tasks_completed' => (int) $project->completed_tasks_count,
                'tasks_overdue' => (int) $project->getAttribute('overdue_tasks_count'),
                'manager' => (new UserSummaryResource($project->manager))->resolve(),
            ])->values()->all(),
        ];
    }

    /**
     * Tasks report: by status / priority, overdue, on-time rate, average days to complete.
     *
     * @return array<string, mixed>
     */
    public function tasks(ReportScope $scope): array
    {
        $byStatus = $this->reports->taskCountsByStatus($scope);
        $total = array_sum($byStatus);
        $completedInPeriod = $this->reports->completedTasks($scope);
        $withDueDate = $completedInPeriod->filter(fn (array $task) => $task['due_date'] !== null);

        return [
            'summary' => [
                'total' => $total,
                'completed' => $byStatus[TaskStatus::Completed->value],
                'pending' => $byStatus[TaskStatus::Pending->value],
                'overdue' => $this->reports->tasks($scope)
                    ->where('status', '!=', TaskStatus::Completed->value)
                    ->whereDate('due_date', '<', today())
                    ->count(),
                'completion_rate' => Metrics::rate($byStatus[TaskStatus::Completed->value], $total),
                'completed_in_period' => $completedInPeriod->count(),
                'on_time_rate' => Metrics::rate(
                    $withDueDate->filter(fn (array $task) => $task['completed_at']->lte($task['due_date']))->count(),
                    $withDueDate->count(),
                ),
                'average_days_to_complete' => $completedInPeriod->isEmpty()
                    ? null
                    : round($completedInPeriod->avg(fn (array $task) => $task['created_at']->diffInHours($task['completed_at']) / 24), 1),
            ],
            'by_status' => collect(TaskStatus::cases())->map(fn (TaskStatus $status) => [
                'status' => $status->toArray(),
                'count' => $byStatus[$status->value],
            ])->all(),
            'by_priority' => collect($this->reports->taskCountsByPriority($scope))
                ->map(fn (int $count, string $priority) => ['priority' => $priority, 'count' => $count])
                ->values()
                ->all(),
            'completed_per_month' => $this->onTimePerMonth($scope),
        ];
    }

    /**
     * Teams report: performance and workload per team.
     *
     * @return list<array<string, mixed>>
     */
    public function teams(ReportScope $scope): array
    {
        $stats = $this->reports->taskStatsPerTeam($scope);
        $empty = ['assigned' => 0, 'completed' => 0, 'open' => 0, 'overdue' => 0];

        return $this->reports->teamsInScope($scope)->map(function (Team $team) use ($stats, $empty) {
            $teamStats = $stats[$team->id] ?? $empty;
            $members = (int) $team->members_count;

            return [
                'id' => $team->id,
                'name' => $team->name,
                'color' => $team->color,
                'members_count' => $members,
                'active_projects' => (int) $team->getAttribute('active_projects_count'),
                ...$teamStats,
                'completion_rate' => Metrics::rate($teamStats['completed'], $teamStats['assigned']),
                // Workload: open tasks per member.
                'open_tasks_per_member' => $members > 0 ? round($teamStats['open'] / $members, 1) : 0.0,
            ];
        })->values()->all();
    }

    /**
     * One person's report (Profile > Reports): tasks, files, meetings and a productivity score.
     *
     * Productivity score (0-100) = 60% completion rate + 30% on-time rate + 10% meeting attendance.
     *
     * @return array<string, mixed>
     */
    public function user(User $user, ReportScope $scope): array
    {
        $tasks = $this->reports->tasks($scope)->whereHas('assignees', fn ($query) => $query->whereKey($user->id));

        $assigned = (clone $tasks)->count();
        $completed = (clone $tasks)->where('status', TaskStatus::Completed->value)->count();
        $overdue = (clone $tasks)->where('status', '!=', TaskStatus::Completed->value)->whereDate('due_date', '<', today())->count();

        $completedInPeriod = (clone $tasks)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$scope->from, $scope->to])
            ->get(['completed_at', 'due_date']);

        $withDueDate = $completedInPeriod->filter(fn ($task) => $task->due_date !== null);
        $onTime = $withDueDate->filter(fn ($task) => $task->completed_at->lte($task->due_date->copy()->endOfDay()))->count();

        $meetings = $this->reports->meetingAttendance($user, $scope);

        $completionRate = Metrics::rate($completed, $assigned);
        $onTimeRate = Metrics::rate($onTime, $withDueDate->count());
        $attendanceRate = Metrics::rate($meetings['attended'], $meetings['invited']);

        return [
            'user' => (new UserSummaryResource($user))->resolve(),
            'tasks' => [
                'assigned' => $assigned,
                'completed' => $completed,
                'open' => $assigned - $completed,
                'overdue' => $overdue,
                'completion_rate' => $completionRate,
                'completed_in_period' => $completedInPeriod->count(),
                'on_time_rate' => $onTimeRate,
            ],
            'files_uploaded' => $this->reports->filesUploadedBy($user, $scope),
            'meetings' => [...$meetings, 'attendance_rate' => $attendanceRate],
            'productivity_score' => $assigned === 0 && $meetings['invited'] === 0
                ? null
                : (int) round(0.6 * $completionRate + 0.3 * $onTimeRate + 0.1 * $attendanceRate),
            'completed_per_month' => collect($scope->months())->map(fn (string $month) => [
                'month' => $month,
                'completed' => $completedInPeriod->filter(fn ($task) => $task->completed_at->format('Y-m') === $month)->count(),
            ])->all(),
        ];
    }

    /**
     * Members of the team with their task numbers (the "12/20" on the Teams tab).
     *
     * @return list<array<string, mixed>>
     */
    public function teamWorkload(Team $team, ReportScope $scope): array
    {
        $stats = $this->reports->taskStatsPerAssignee($scope, $team);
        $empty = ['assigned' => 0, 'completed' => 0, 'open' => 0, 'overdue' => 0];

        return $team->members()->get()->map(fn (User $member) => [
            ...(new UserSummaryResource($member))->resolve(),
            'position' => $member->membership->position,
            'is_leader' => $member->membership->is_leader,
            ...($stats[$member->id] ?? $empty),
            'completion_rate' => Metrics::rate(($stats[$member->id] ?? $empty)['completed'], ($stats[$member->id] ?? $empty)['assigned']),
        ])->values()->all();
    }

    private function taskCompletionRate(ReportScope $scope): int
    {
        $created = $this->reports->tasks($scope)->whereBetween('created_at', [$scope->from, $scope->to]);

        return Metrics::rate(
            (clone $created)->where('status', TaskStatus::Completed->value)->count(),
            (clone $created)->count(),
        );
    }

    /**
     * Completed projects / projects that existed at that date.
     */
    private function projectCompletionRate(ReportScope $scope, CarbonImmutable $at): int
    {
        $projects = $this->reports->projects($scope)->where('created_at', '<=', $at);

        return Metrics::rate(
            (clone $projects)->whereNotNull('completed_at')->where('completed_at', '<=', $at)->count(),
            (clone $projects)->count(),
        );
    }

    private function teamActivityRate(ReportScope $scope): int
    {
        $activity = $this->reports->memberActivity($scope);

        return Metrics::rate($activity['active'], $activity['total']);
    }

    /**
     * "Task Completion Time vs Deadline": per month, tasks completed on time / late.
     *
     * @return list<array<string, mixed>>
     */
    private function onTimePerMonth(ReportScope $scope): array
    {
        $completed = $this->reports->completedTasks($scope)->groupBy(fn (array $task) => $task['completed_at']->format('Y-m'));

        return collect($scope->months())->map(function (string $month) use ($completed) {
            $tasks = $completed->get($month, collect());
            $withDue = $tasks->filter(fn (array $task) => $task['due_date'] !== null);
            $onTime = $withDue->filter(fn (array $task) => $task['completed_at']->lte($task['due_date']))->count();

            return [
                'month' => $month,
                'completed' => $tasks->count(),
                'on_time' => $onTime,
                'late' => $withDue->count() - $onTime,
                'on_time_rate' => Metrics::rate($onTime, $withDue->count()),
            ];
        })->all();
    }

    /**
     * "Projects Progress": at the end of each month, % of all tasks created so far that were completed.
     *
     * @return list<array{month: string, progress: int}>
     */
    private function progressPerMonth(ReportScope $scope): array
    {
        $created = $this->reports->taskCreationDates($scope);
        $completedBefore = $this->reports->tasks($scope)->whereNotNull('completed_at')->toBase()->pluck('completed_at')
            ->map(fn ($date) => CarbonImmutable::parse($date));

        return collect($scope->months())->map(function (string $month) use ($created, $completedBefore) {
            $end = CarbonImmutable::parse($month.'-01')->endOfMonth();

            return [
                'month' => $month,
                'progress' => Metrics::rate(
                    $completedBefore->filter(fn ($date) => $date->lte($end))->count(),
                    $created->filter(fn ($date) => $date->lte($end))->count(),
                ),
            ];
        })->all();
    }

    /**
     * "Worker Activity Rate": tasks completed and distinct people who completed them, per month.
     *
     * @return list<array{month: string, completed_tasks: int, active_members: int}>
     */
    private function workerActivityPerMonth(ReportScope $scope): array
    {
        $completed = $this->reports->completedTasks($scope)->groupBy(fn (array $task) => $task['completed_at']->format('Y-m'));
        $active = $this->reports->activeMembersPerMonth($scope);

        return collect($scope->months())->map(fn (string $month) => [
            'month' => $month,
            'completed_tasks' => $completed->get($month, collect())->count(),
            'active_members' => $active[$month] ?? 0,
        ])->all();
    }
}
