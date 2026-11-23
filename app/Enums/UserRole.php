<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum UserRole: string
{
    use HasEnumHelpers;

    case Admin = 'admin';
    case ProjectManager = 'project_manager';
    case TeamMember = 'team_member';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::ProjectManager => 'Project Manager',
            self::TeamMember => 'Team Member',
        };
    }
}
