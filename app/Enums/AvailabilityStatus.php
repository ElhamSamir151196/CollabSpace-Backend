<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum AvailabilityStatus: string
{
    use HasEnumHelpers;

    case Available = 'available';
    case Busy = 'busy';
    case Away = 'away';
    case Offline = 'offline';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Busy => 'Busy',
            self::Away => 'Away',
            self::Offline => 'Offline',
        };
    }
}
