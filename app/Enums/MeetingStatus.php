<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum MeetingStatus: string
{
    use HasEnumHelpers;

    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
        };
    }
}
