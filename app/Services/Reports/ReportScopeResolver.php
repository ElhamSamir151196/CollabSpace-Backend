<?php

namespace App\Services\Reports;

use App\DTOs\Reports\ReportScope;
use App\Enums\Permission;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Decides which projects a user's report / dashboard covers.
 *  - Reports: admins (reports.view-all) see everything; project managers
 *    (reports.view-project) see the projects they manage.
 *  - Dashboard: every project the user can see.
 * A requested project_id outside that set is refused (403).
 */
class ReportScopeResolver
{
    /**
     * Default period: the current month and the 5 before it.
     */
    public function forReports(User $user, ?int $projectId, ?string $from, ?string $to): ReportScope
    {
        $allowed = match (true) {
            $user->can(Permission::ReportsViewAll->value) => null,
            $user->can(Permission::ReportsViewProject->value) => $this->ids(Project::query()->where('manager_id', $user->id)),
            default => throw new AuthorizationException,
        };

        return $this->build($allowed, $projectId, $from, $to);
    }

    public function forDashboard(User $user, ?int $projectId, ?string $from = null, ?string $to = null): ReportScope
    {
        $allowed = $user->can(Permission::ProjectsManageAll->value)
            ? null
            : $this->ids(Project::query()->visibleTo($user));

        return $this->build($allowed, $projectId, $from, $to);
    }

    /**
     * @param  list<int>|null  $allowed
     */
    private function build(?array $allowed, ?int $projectId, ?string $from, ?string $to): ReportScope
    {
        if ($projectId !== null) {
            if ($allowed !== null && ! in_array($projectId, $allowed, true)) {
                throw new AuthorizationException;
            }

            $allowed = [$projectId];
        }

        $end = $to !== null ? CarbonImmutable::parse($to)->endOfDay() : CarbonImmutable::now()->endOfDay();
        $start = $from !== null ? CarbonImmutable::parse($from)->startOfDay() : $end->subMonths(5)->startOfMonth();

        return new ReportScope($allowed, $start, $end);
    }

    /**
     * @param  Builder<Project>  $query
     * @return list<int>
     */
    private function ids($query): array
    {
        return $query->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }
}
