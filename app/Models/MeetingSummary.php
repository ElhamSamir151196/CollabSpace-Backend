<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The AI summary of a meeting (SRS: summary, decisions, action items,
 * assigned tasks, risks, next steps).
 *
 * @property int $id
 * @property int $meeting_id
 * @property string $summary
 * @property list<string> $key_points
 * @property list<string> $decisions
 * @property list<array{task: string, owner: string|null}> $action_items
 * @property list<string> $risks
 * @property list<string> $next_steps
 * @property string $provider
 * @property string $model
 * @property int|null $generated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Meeting $meeting
 * @property-read User|null $generator
 */
#[Fillable(['meeting_id', 'summary', 'key_points', 'decisions', 'action_items', 'risks', 'next_steps', 'provider', 'model', 'generated_by'])]
class MeetingSummary extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key_points' => 'array',
            'decisions' => 'array',
            'action_items' => 'array',
            'risks' => 'array',
            'next_steps' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Meeting, $this>
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
