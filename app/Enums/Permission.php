<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * Every permission in the system, in one place.
 * Which role gets which permission is defined in forRole().
 *
 * Note: day-to-day access (e.g. a member updating a task assigned to them)
 * is decided by Policies using project/team membership, not by these.
 */
enum Permission: string
{
    use HasEnumHelpers;

    case UsersManage = 'users.manage';
    case ActivityLogView = 'activity-log.view';
    case SettingsManage = 'settings.manage';
    case AiConfigure = 'ai.configure';

    case ProjectsCreate = 'projects.create';
    case ProjectsManageAll = 'projects.manage-all';

    case TeamsManage = 'teams.manage';
    case TeamsAssign = 'teams.assign';

    case TasksManage = 'tasks.manage';
    case MeetingsManage = 'meetings.manage';

    case ChatsViewAll = 'chats.view-all';

    case ReportsViewAll = 'reports.view-all';
    case ReportsViewProject = 'reports.view-project';

    public function label(): string
    {
        return match ($this) {
            self::UsersManage => 'Manage users',
            self::ActivityLogView => 'View activity log',
            self::SettingsManage => 'Manage system settings',
            self::AiConfigure => 'Configure AI',
            self::ProjectsCreate => 'Create projects',
            self::ProjectsManageAll => 'Manage all projects',
            self::TeamsManage => 'Create, edit and delete teams',
            self::TeamsAssign => 'Assign teams and members to projects',
            self::TasksManage => 'Create, edit and delete tasks',
            self::MeetingsManage => 'Schedule, cancel and reschedule meetings',
            self::ChatsViewAll => 'Access all project chats',
            self::ReportsViewAll => 'View all reports',
            self::ReportsViewProject => 'View and export project reports',
        };
    }

    /**
     * @return list<self>
     */
    public static function forRole(UserRole $role): array
    {
        return match ($role) {
            UserRole::Admin => self::cases(),
            UserRole::ProjectManager => [
                self::ProjectsCreate,
                self::TeamsAssign,
                self::TasksManage,
                self::MeetingsManage,
                self::ReportsViewProject,
            ],
            UserRole::TeamMember => [],
        };
    }
}
