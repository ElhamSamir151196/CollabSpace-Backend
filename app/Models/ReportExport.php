<?php

namespace App\Models;

use App\Enums\ReportType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property ReportType $type
 * @property int|null $project_id
 * @property int|null $subject_user_id
 * @property string|null $note
 * @property Carbon $period_from
 * @property Carbon $period_to
 * @property string $disk
 * @property string $path
 * @property int $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Project|null $project
 * @property-read User|null $subject
 */
#[Fillable(['user_id', 'type', 'project_id', 'subject_user_id', 'note', 'period_from', 'period_to', 'disk', 'path', 'size'])]
class ReportExport extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ReportType::class,
            'period_from' => 'date',
            'period_to' => 'date',
            'size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    /**
     * "project-report-alpha-2025-12-15.pdf"
     */
    public function fileName(): string
    {
        $parts = array_filter([
            Str::slug($this->type->label()),
            $this->project?->slug,
            $this->created_at?->format('Y-m-d'),
        ]);

        return implode('-', $parts).'.pdf';
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
