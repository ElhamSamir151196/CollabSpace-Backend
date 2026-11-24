<?php

namespace App\Interfaces\Repositories;

use App\DTOs\Reports\ReportScope;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Read-only queries behind the dashboard and the reports.
 */
interface ReportRepositoryInterface
{
    /**
     * Tasks of the scope's projects (deleted projects excluded).
     *
     * @return Builder<Task>
     */
    public function tasks(ReportScope $scope): Builder;

    /**
     * @return Builder<Project>
     */
    public function projects(ReportScope $scope): Builder;

    /**
     * Current number of tasks per status: ['pending' => 3, 'in_progress' => 1, ...].
     *
     * @return array<string, int>
     */
    public function taskCountsByStatus(ReportScope $scope): array;

    /**
     * @return array<string, int>
     */
    public function taskCountsByPriority(ReportScope $scope): array;

    /**
     * completed_at / due_date of tasks completed inside the period.
     *
     * @return BaseCollection<int, array{completed_at: Carbon, due_date: Carbon|null, created_at: Carbon}>
     */
    public function completedTasks(ReportScope $scope): BaseCollection;

    /**
     * created_at of every task created up to the end of the period.
     *
     * @return BaseCollection<int, Carbon>
     */
    public function taskCreationDates(ReportScope $scope): BaseCollection;

    /**
     * Per month: distinct assignees of the tasks completed that month.
     *
     * @return array<string, int> "2025-07" => 4
     */
    public function activeMembersPerMonth(ReportScope $scope): array;

    /**
     * Projects with task counts (tasks, completed, overdue) and their manager.
     *
     * @return Collection<int, Project>
     */
    public function projectRows(ReportScope $scope): Collection;

    /**
     * Teams assigned to the scope's projects, with member counts.
     *
     * @return Collection<int, Team>
     */
    public function teamsInScope(ReportScope $scope): Collection;

    /**
     * Per team id: ['assigned' => .., 'completed' => .., 'open' => .., 'overdue' => ..].
     *
     * @return array<int, array{assigned: int, completed: int, open: int, overdue: int}>
     */
    public function taskStatsPerTeam(ReportScope $scope): array;

    /**
     * Per user id (assignees), same shape as taskStatsPerTeam(). Optional team filter.
     *
     * @return array<int, array{assigned: int, completed: int, open: int, overdue: int}>
     */
    public function taskStatsPerAssignee(ReportScope $scope, ?Team $team = null): array;

    /**
     * Members of the scope's teams who completed at least one task in the period / all of them.
     *
     * @return array{active: int, total: int}
     */
    public function memberActivity(ReportScope $scope): array;

    /**
     * Meetings in the period the user was invited to / attended.
     *
     * @return array{invited: int, attended: int}
     */
    public function meetingAttendance(User $user, ReportScope $scope): array;

    public function filesUploadedBy(User $user, ReportScope $scope): int;
}
