<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum TaskStatus: string
{
    use HasEnumHelpers;

    case Pending = 'pending';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'To-do',
            self::InProgress => 'In Progress',
            self::InReview => 'In Review',
            self::Completed => 'Completed',
        };
    }
}
