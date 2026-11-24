<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * Every kind of in-app notification. Stored in notifications.type,
 * so the frontend can pick an icon / translate by kind.
 */
enum NotificationKind: string
{
    use HasEnumHelpers;

    case AddedToProject = 'added_to_project';
    case AddedToTeam = 'added_to_team';
    case TaskAssigned = 'task_assigned';
    case TaskStatusChanged = 'task_status_changed';
    case TaskDueSoon = 'task_due_soon';
    case Mentioned = 'mentioned';
    case MeetingInvited = 'meeting_invited';
    case MeetingRescheduled = 'meeting_rescheduled';
    case MeetingCancelled = 'meeting_cancelled';
    case MeetingReminder = 'meeting_reminder';

    public function label(): string
    {
        return match ($this) {
            self::AddedToProject => 'Added to a project',
            self::AddedToTeam => 'Added to a team',
            self::TaskAssigned => 'New task',
            self::TaskStatusChanged => 'Task updated',
            self::TaskDueSoon => 'Task due soon',
            self::Mentioned => 'You were mentioned',
            self::MeetingInvited => 'Meeting invitation',
            self::MeetingRescheduled => 'Meeting rescheduled',
            self::MeetingCancelled => 'Meeting cancelled',
            self::MeetingReminder => 'Meeting starting soon',
        };
    }
}
