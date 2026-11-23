<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum AttendanceStatus: string
{
    use HasEnumHelpers;

    case Invited = 'invited';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Attended = 'attended';

    public function label(): string
    {
        return match ($this) {
            self::Invited => 'Invited',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::Attended => 'Attended',
        };
    }
}
