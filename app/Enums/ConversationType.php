<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum ConversationType: string
{
    use HasEnumHelpers;

    case Direct = 'direct';
    case Project = 'project';
    case Team = 'team';

    public function label(): string
    {
        return match ($this) {
            self::Direct => 'Direct',
            self::Project => 'Project',
            self::Team => 'Team',
        };
    }
}
