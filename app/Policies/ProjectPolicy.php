<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Project;
use App\Models\User;

/**
 * - Admins (projects.manage-all): everything on every project.
 * - Project Managers (projects.create): create projects, and fully manage the ones they manage.
 * - Everyone else: view the projects they belong to.
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $this->managesAll($user) || $project->hasMember($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ProjectsCreate->value);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->managesAll($user)
            || ($project->isManagedBy($user) && $user->can(Permission::ProjectsCreate->value));
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /**
     * Assign / unassign teams and guests.
     */
    public function manageMembers(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /**
     * Only admins can hand a project over to another manager.
     */
    public function changeManager(User $user): bool
    {
        return $this->managesAll($user);
    }

    private function managesAll(User $user): bool
    {
        return $user->can(Permission::ProjectsManageAll->value);
    }
}
