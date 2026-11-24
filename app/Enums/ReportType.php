<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * "Select Report Type" in the Submit-a-detailed-report form.
 */
enum ReportType: string
{
    use HasEnumHelpers;

    case Overview = 'overview';
    case Project = 'project';
    case Task = 'task';
    case Team = 'team';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Overview => 'Overview Report',
            self::Project => 'Project Report',
            self::Task => 'Task Report',
            self::Team => 'Team Report',
            self::User => 'User Report',
        };
    }
}
