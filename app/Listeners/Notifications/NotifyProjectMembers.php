<?php

namespace App\Listeners\Notifications;

use App\Events\ProjectCreated;
use App\Events\ProjectGuestsAdded;
use App\Events\ProjectTeamsAssigned;
use App\Events\TeamMembersAdded;
use App\Models\TeamMember;
use App\Notifications\AddedToProjectNotification;
use App\Notifications\AddedToTeamNotification;
use App\Services\Notifications\Recipients;
use Illuminate\Support\Facades\Notification;

/**
 * "You were added to the project / team" notifications.
 */
class NotifyProjectMembers
{
    public function __construct(private readonly Recipients $recipients) {}

    /**
     * Everyone in the new project: its manager, its teams' members and its guests.
     */
    public function handleProjectCreated(ProjectCreated $event): void
    {
        $project = $event->project;

        $ids = collect([$project->manager_id])
            ->merge($project->guests()->pluck('users.id'))
            ->merge($this->membersOfTeams($project->teams()->pluck('teams.id')->all()));

        Notification::send(
            $this->recipients->of($ids, except: $event->createdBy),
            new AddedToProjectNotification($project, $event->createdBy),
        );
    }

    public function handleProjectTeamsAssigned(ProjectTeamsAssigned $event): void
    {
        Notification::send(
            $this->recipients->of($this->membersOfTeams($event->teamIds), except: $event->by),
            new AddedToProjectNotification($event->project, $event->by),
        );
    }

    public function handleProjectGuestsAdded(ProjectGuestsAdded $event): void
    {
        Notification::send(
            $this->recipients->of($event->userIds, except: $event->by),
            new AddedToProjectNotification($event->project, $event->by),
        );
    }

    public function handleTeamMembersAdded(TeamMembersAdded $event): void
    {
        Notification::send(
            $this->recipients->of($event->userIds, except: $event->by),
            new AddedToTeamNotification($event->team, $event->by),
        );
    }

    /**
     * @param  list<int>  $teamIds
     * @return list<int>
     */
    private function membersOfTeams(array $teamIds): array
    {
        return TeamMember::query()->whereIn('team_id', $teamIds)->pluck('user_id')->map(fn ($id) => (int) $id)->all();
    }
}
