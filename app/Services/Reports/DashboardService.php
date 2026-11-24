<?php

namespace App\Services\Reports;

use App\DTOs\Reports\ReportScope;
use App\Enums\Permission;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Filters\MeetingFilter;
use App\Interfaces\Repositories\AttachmentRepositoryInterface;
use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Interfaces\Repositories\ReportRepositoryInterface;
use App\Models\Attachment;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Support\Metrics;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Everything on the Dashboard screen, for the signed-in user.
 */
class DashboardService
{
    public function __construct(
        private readonly ReportRepositoryInterface $reports,
        private readonly AttachmentRepositoryInterface $attachments,
        private readonly MeetingRepositoryInterface $meetings,
    ) {}

    /**
     * The four cards: Pending / In Progress / Completed / Completion Rate.
     *  - count: tasks in that column now; total: all tasks.
     *  - trend: % change "from last month":
     *      columns   -> new tasks this month in that column vs last month,
     *      completed -> tasks completed this month vs last month,
     *      rate      -> rate now vs rate at the end of last month (in points).
     *
     * @return array<string, mixed>
     */
    public function taskStats(ReportScope $scope): array
    {
        $counts = $this->reports->taskCountsByStatus($scope);
        $total = array_sum($counts);

        $thisMonth = [now()->startOfMonth(), now()];
        $lastMonth = [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()];

        $created = fn (TaskStatus $status, array $range) => $this->reports->tasks($scope)
            ->where('status', $status->value)
            ->whereBetween('created_at', $range)
            ->count();

        $completedBetween = fn (array $range) => $this->reports->tasks($scope)->whereBetween('completed_at', $range)->count();

        $column = fn (TaskStatus $status) => [
            'count' => $counts[$status->value],
            'total' => $total,
            'trend' => Metrics::percentChange($created($status, $thisMonth), $created($status, $lastMonth)),
        ];

        $beforeThisMonth = $this->reports->tasks($scope)->where('created_at', '<', $thisMonth[0]);
        $previousRate = Metrics::rate(
            (clone $beforeThisMonth)->where('completed_at', '<', $thisMonth[0])->count(),
            (clone $beforeThisMonth)->count(),
        );

        return [
            'pending' => $column(TaskStatus::Pending),
            'in_progress' => $column(TaskStatus::InProgress),
            'in_review' => $column(TaskStatus::InReview),
            'completed' => [
                'count' => $counts[TaskStatus::Completed->value],
                'total' => $total,
                'trend' => Metrics::percentChange($completedBetween($thisMonth), $completedBetween($lastMonth)),
            ],
            'completion_rate' => Metrics::rateWithChange(Metrics::rate($counts[TaskStatus::Completed->value], $total), $previousRate),
        ];
    }

    /**
     * "Project overview" chart: tasks created and completed per month of the year.
     *
     * @return list<array{month: string, created: int, completed: int}>
     */
    public function monthlyChart(ReportScope $scope, int $year): array
    {
        $endOfYear = CarbonImmutable::create($year)->endOfYear();
        $endOfThisMonth = CarbonImmutable::now()->endOfMonth();

        // The current year stops at this month (no empty future months).
        $yearScope = new ReportScope(
            $scope->projectIds,
            CarbonImmutable::create($year)->startOfYear(),
            $endOfYear->lt($endOfThisMonth) ? $endOfYear : $endOfThisMonth,
        );

        $created = $this->reports->taskCreationDates($yearScope)
            ->filter(fn ($date) => $date->gte($yearScope->from))
            ->countBy(fn ($date) => $date->format('Y-m'));

        $completed = $this->reports->completedTasks($yearScope)
            ->countBy(fn (array $task) => $task['completed_at']->format('Y-m'));

        return collect($yearScope->months())->map(fn (string $month) => [
            'month' => $month,
            'created' => (int) $created->get($month, 0),
            'completed' => (int) $completed->get($month, 0),
        ])->all();
    }

    /**
     * "Active Projects": in-progress projects with their task progress.
     *
     * @return Collection<int, Project>
     */
    public function activeProjects(ReportScope $scope, int $limit = 5): Collection
    {
        return $this->reports->projects($scope)
            ->where('status', ProjectStatus::InProgress->value)
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn (Builder $tasks) => $tasks->where('status', TaskStatus::Completed->value),
            ])
            ->orderBy('deadline')
            ->limit($limit)
            ->get();
    }

    /**
     * "Tasks": my open tasks, the ones due first on top.
     *
     * @return Collection<int, Task>
     */
    public function myTasks(User $user, ReportScope $scope, int $limit = 5): Collection
    {
        return $this->reports->tasks($scope)
            ->whereHas('assignees', fn (Builder $users) => $users->whereKey($user->id))
            ->where('status', '!=', TaskStatus::Completed->value)
            ->with(['project', 'team', 'reviewer', 'assignees'])
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->limit($limit)
            ->get();
    }

    /**
     * "Teams": my teams (all teams for admins) with members and active projects.
     *
     * @return Collection<int, Team>
     */
    public function teams(User $user): Collection
    {
        return Team::query()
            ->when(
                ! $user->can(Permission::ProjectsManageAll->value),
                fn (Builder $teams) => $teams->whereHas('members', fn (Builder $members) => $members->whereKey($user->id)),
            )
            ->withCount([
                'members',
                'projects as active_projects_count' => fn (Builder $projects) => $projects->where('status', ProjectStatus::InProgress->value),
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Attachment>
     */
    public function recentFiles(User $user, int $limit = 3): Collection
    {
        return $this->attachments->recentVisibleTo($user, $limit);
    }

    /**
     * "Up coming meetings": meetings I'm invited to that have not ended.
     *
     * @return Collection<int, Meeting>
     */
    public function upcomingMeetings(User $user, int $limit = 3): Collection
    {
        return $this->meetings->calendarFor($user, new MeetingFilter(['upcoming' => true, 'participant_id' => $user->id]), $limit);
    }
}
