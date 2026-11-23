<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum MeetingNoteType: string
{
    use HasEnumHelpers;

    case Note = 'note';
    case Comment = 'comment';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Note',
            self::Comment => 'Comment',
        };
    }
}
