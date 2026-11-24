<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Project;
use App\Models\User;

/**
 * Reports are not tied to one model, so these abilities are registered
 * with Gate::define() in AppServiceProvider ("viewReports", "viewUserReport").
 *
 * - Project / task / team reports: admins (all projects) and project managers (their projects).
 * - A person's report: themselves, admins, and the manager of a project they belong to.
 */
class ReportPolicy
{
    public function viewReports(User $user): bool
    {
        return $user->canAny([Permission::ReportsViewAll->value, Permission::ReportsViewProject->value]);
    }

    public function viewUserReport(User $viewer, User $target): bool
    {
        if ($viewer->is($target) || $viewer->can(Permission::ReportsViewAll->value)) {
            return true;
        }

        if (! $viewer->can(Permission::ReportsViewProject->value)) {
            return false;
        }

        return Project::query()
            ->where('manager_id', $viewer->id)
            ->visibleTo($target)
            ->exists();
    }
}
