<?php

namespace App\Filters;

use App\Enums\FileType;
use App\Models\Message;
use App\Models\Project;
use App\Models\Task;

/**
 * ?search= ?type=image|document|... ?source=project|task|message ?uploaded_by= ?sort=-created_at|size ...
 */
class AttachmentFilter extends QueryFilter
{
    protected array $sortable = ['created_at', 'original_name', 'size'];

    protected string $defaultSort = '-created_at';

    public function search(string $term): void
    {
        $this->whereLike(['original_name'], $term);
    }

    public function type(string $type): void
    {
        $fileType = FileType::from($type);

        $fileType === FileType::Other
            ? $this->builder->whereNotIn('extension', FileType::allowedExtensions())
            : $this->builder->whereIn('extension', $fileType->extensions());
    }

    public function source(string $source): void
    {
        $this->builder->where('attachable_type', match ($source) {
            'task' => Task::class,
            'message' => Message::class,
            default => Project::class,
        });
    }

    public function uploadedBy(int|string $userId): void
    {
        $this->builder->where('uploaded_by', (int) $userId);
    }
}
