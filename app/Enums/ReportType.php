<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum ReportType: string
{
    use HasEnumHelpers;

    case Project = 'project';
    case Task = 'task';
    case Team = 'team';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Project => 'Project Report',
            self::Task => 'Task Report',
            self::Team => 'Team Report',
            self::User => 'User Report',
        };
    }
}
