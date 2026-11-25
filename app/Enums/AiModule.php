<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * The kinds of data the AI is allowed to read. The admin chooses which
 * ones are enabled ("controlled access" in the SRS).
 */
enum AiModule: string
{
    use HasEnumHelpers;

    case Projects = 'projects';
    case Tasks = 'tasks';
    case Teams = 'teams';
    case Meetings = 'meetings';
    case Reports = 'reports';
    case Files = 'files';

    public function label(): string
    {
        return match ($this) {
            self::Projects => 'Projects',
            self::Tasks => 'Tasks',
            self::Teams => 'Teams',
            self::Meetings => 'Meetings',
            self::Reports => 'Reports',
            self::Files => 'Files (names only)',
        };
    }
}
