<?php

namespace App\Models;

use App\Enums\FileType;
use App\Traits\Filterable;
use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * An uploaded file ("Files" in the UI). Stored on a private disk and
 * downloaded only through a short-lived signed URL.
 *
 * @property int $id
 * @property string $attachable_type
 * @property int $attachable_id
 * @property int|null $project_id
 * @property int|null $uploaded_by
 * @property string $original_name
 * @property string $disk
 * @property string $path
 * @property string|null $mime_type
 * @property string $extension
 * @property int $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project|Task|Model $attachable
 * @property-read Project|null $project
 * @property-read User|null $uploader
 */
#[Fillable([
    'attachable_type',
    'attachable_id',
    'project_id',
    'uploaded_by',
    'original_name',
    'disk',
    'path',
    'mime_type',
    'extension',
    'size',
])]
class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use Filterable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
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
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Files of projects the user can see.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->whereHas('project', fn (Builder $projects) => $projects->visibleTo($user));
    }

    public function type(): FileType
    {
        return FileType::fromExtension($this->extension);
    }

    /**
     * "project" or "task" — what the file was attached to.
     */
    public function source(): string
    {
        return match ($this->attachable_type) {
            Task::class => 'task',
            Project::class => 'project',
            default => 'other',
        };
    }

    public function isUploadedBy(User $user): bool
    {
        return $this->uploaded_by === $user->id;
    }
}
