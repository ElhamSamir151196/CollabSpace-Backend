<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\User;

/**
 * - Project members see the project's meetings (the calendar).
 * - The project's manager (and admins) schedule, edit, reschedule, cancel,
 *   delete meetings and manage the participants.
 * - The person who created the meeting enters its transcript and cover image.
 * - Participants answer the invitation and join; participants and managers
 *   write notes and comments.
 */
class MeetingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Meeting $meeting): bool
    {
        return $user->can('view', $meeting->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->manages($user, $project);
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $this->manages($user, $meeting->project);
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $this->manages($user, $meeting->project);
    }

    public function manageParticipants(User $user, Meeting $meeting): bool
    {
        return $this->manages($user, $meeting->project);
    }

    /**
     * Transcript and cover image: the meeting's creator (admins as a fallback).
     */
    public function manageContent(User $user, Meeting $meeting): bool
    {
        return $meeting->isCreatedBy($user) || $user->can(Permission::ProjectsManageAll->value);
    }

    public function respond(User $user, Meeting $meeting): bool
    {
        return $meeting->hasParticipant($user);
    }

    public function join(User $user, Meeting $meeting): bool
    {
        return $meeting->hasParticipant($user) || $this->manages($user, $meeting->project);
    }

    public function addNote(User $user, Meeting $meeting): bool
    {
        return $meeting->hasParticipant($user) || $this->manages($user, $meeting->project);
    }

    private function manages(User $user, Project $project): bool
    {
        return $user->can(Permission::MeetingsManage->value) && $user->can('update', $project);
    }
}
