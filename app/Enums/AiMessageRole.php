<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum AiMessageRole: string
{
    use HasEnumHelpers;

    case User = 'user';
    case Assistant = 'assistant';

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Assistant => 'Assistant',
        };
    }
}
