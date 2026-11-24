<?php

namespace App\Repositories\Eloquent;

use App\DTOs\Reports\ReportScope;
use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Interfaces\Repositories\ReportRepositoryInterface;
use App\Models\Attachment;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregations are done with portable SQL (MySQL in the app, SQLite in tests);
 * grouping by month is done in PHP for the same reason.
 */
class ReportRepository implements ReportRepositoryInterface
{
    public function tasks(ReportScope $scope): Builder
    {
        return $scope->applyTo(Task::query()->whereHas('project'));
    }

    public function projects(ReportScope $scope): Builder
    {
        return $scope->applyTo(Project::query(), 'id');
    }

    public function taskCountsByStatus(ReportScope $scope): array
    {
        return $this->countBy($scope, 'status', TaskStatus::values());
    }

    public function taskCountsByPriority(ReportScope $scope): array
    {
        return $this->countBy($scope, 'priority', Priority::values());
    }

    public function completedTasks(ReportScope $scope): BaseCollection
    {
        return $this->tasks($scope)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$scope->from, $scope->to])
            ->toBase()
            ->get(['completed_at', 'due_date', 'created_at'])
            ->map(fn ($row) => [
                'completed_at' => Carbon::parse($row->completed_at),
                'due_date' => $row->due_date !== null ? Carbon::parse($row->due_date)->endOfDay() : null,
                'created_at' => Carbon::parse($row->created_at),
            ]);
    }

    public function taskCreationDates(ReportScope $scope): BaseCollection
    {
        return $this->tasks($scope)
            ->where('created_at', '<=', $scope->to)
            ->toBase()
            ->pluck('created_at')
            ->map(fn ($date) => Carbon::parse($date))
            ->values();
    }

    public function activeMembersPerMonth(ReportScope $scope): array
    {
        $rows = DB::table('task_user')
            ->join('tasks', 'tasks.id', '=', 'task_user.task_id')
            ->whereNull('tasks.deleted_at')
            ->whereNotNull('tasks.completed_at')
            ->whereBetween('tasks.completed_at', [$scope->from, $scope->to])
            ->when($scope->projectIds !== null, fn ($query) => $query->whereIn('tasks.project_id', $scope->projectIds ?? []))
            ->get(['task_user.user_id', 'tasks.completed_at']);

        return $rows
            ->groupBy(fn ($row) => Carbon::parse($row->completed_at)->format('Y-m'))
            ->map(fn (BaseCollection $month) => $month->pluck('user_id')->unique()->count())
            ->all();
    }

    public function projectRows(ReportScope $scope): Collection
    {
        return $this->projects($scope)
            ->with('manager')
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn (Builder $tasks) => $tasks->where('status', TaskStatus::Completed->value),
                'tasks as overdue_tasks_count' => fn (Builder $tasks) => $tasks
                    ->where('status', '!=', TaskStatus::Completed->value)
                    ->whereDate('due_date', '<', today()),
            ])
            ->orderBy('deadline')
            ->get();
    }

    public function teamsInScope(ReportScope $scope): Collection
    {
        return Team::query()
            ->whereHas('projects', fn (Builder $projects) => $scope->applyTo($projects, 'projects.id'))
            ->withCount([
                'members',
                'projects as active_projects_count' => fn (Builder $projects) => $scope
                    ->applyTo($projects, 'projects.id')
                    ->where('status', ProjectStatus::InProgress->value),
            ])
            ->orderBy('name')
            ->get();
    }

    public function taskStatsPerTeam(ReportScope $scope): array
    {
        return $this->statsGroupedBy($this->tasks($scope)->toBase(), 'tasks.team_id');
    }

    public function taskStatsPerAssignee(ReportScope $scope, ?Team $team = null): array
    {
        $query = $this->tasks($scope)
            ->when($team !== null, fn (Builder $tasks) => $tasks->where('team_id', $team?->id))
            ->toBase()
            ->join('task_user', 'task_user.task_id', '=', 'tasks.id');

        return $this->statsGroupedBy($query, 'task_user.user_id');
    }

    public function memberActivity(ReportScope $scope): array
    {
        $teamIds = $this->teamsInScope($scope)->pluck('id');
        $memberIds = TeamMember::query()->whereIn('team_id', $teamIds)->distinct()->pluck('user_id');

        $active = DB::table('task_user')
            ->join('tasks', 'tasks.id', '=', 'task_user.task_id')
            ->whereNull('tasks.deleted_at')
            ->whereIn('task_user.user_id', $memberIds)
            ->whereBetween('tasks.completed_at', [$scope->from, $scope->to])
            ->when($scope->projectIds !== null, fn ($query) => $query->whereIn('tasks.project_id', $scope->projectIds ?? []))
            ->distinct()
            ->count('task_user.user_id');

        return ['active' => $active, 'total' => $memberIds->count()];
    }

    public function meetingAttendance(User $user, ReportScope $scope): array
    {
        $meetings = $scope->applyTo(Meeting::query()->whereHas('project'))
            ->where('status', '!=', MeetingStatus::Cancelled->value)
            ->whereBetween('start_at', [$scope->from, $scope->to])
            ->where('end_at', '<', now())
            ->join('meeting_user', 'meeting_user.meeting_id', '=', 'meetings.id')
            ->where('meeting_user.user_id', $user->id);

        return [
            'invited' => (clone $meetings)->count(),
            'attended' => (clone $meetings)->where('meeting_user.attendance_status', AttendanceStatus::Attended->value)->count(),
        ];
    }

    public function filesUploadedBy(User $user, ReportScope $scope): int
    {
        return $scope->applyTo(Attachment::query())
            ->where('uploaded_by', $user->id)
            ->whereBetween('created_at', [$scope->from, $scope->to])
            ->count();
    }

    /**
     * @param  list<string>  $values
     * @return array<string, int>
     */
    private function countBy(ReportScope $scope, string $column, array $values): array
    {
        $counts = $this->tasks($scope)
            ->toBase()
            ->select($column, DB::raw('count(*) as aggregate'))
            ->groupBy($column)
            ->pluck('aggregate', $column);

        return collect($values)->mapWithKeys(fn (string $value) => [$value => (int) ($counts[$value] ?? 0)])->all();
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array<int, array{assigned: int, completed: int, open: int, overdue: int}>
     */
    private function statsGroupedBy($query, string $groupColumn): array
    {
        $completed = TaskStatus::Completed->value;
        $today = today()->toDateString();

        return $query
            ->select($groupColumn.' as group_id')
            ->selectRaw('count(*) as assigned')
            ->selectRaw('sum(case when tasks.status = ? then 1 else 0 end) as completed', [$completed])
            ->selectRaw('sum(case when tasks.status != ? and tasks.due_date < ? then 1 else 0 end) as overdue', [$completed, $today])
            ->groupBy($groupColumn)
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->group_id => [
                'assigned' => (int) $row->assigned,
                'completed' => (int) $row->completed,
                'open' => (int) $row->assigned - (int) $row->completed,
                'overdue' => (int) $row->overdue,
            ]])
            ->all();
    }
}
