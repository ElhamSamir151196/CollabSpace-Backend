<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum Priority: string
{
    use HasEnumHelpers;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Critical => 'Critical',
        };
    }
}
