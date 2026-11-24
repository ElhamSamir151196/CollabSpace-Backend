<?php

namespace App\Services\Search\Providers;

use App\DTOs\Search\SearchResult;
use App\Interfaces\Search\SearchProvider;
use App\Models\Attachment;
use App\Models\User;
use App\Support\Like;

/**
 * Project and task files (and project-channel chat files) in visible projects.
 * The result opens through GET /files/{id}, which returns fresh download links.
 */
class FileSearch implements SearchProvider
{
    public function key(): string
    {
        return 'files';
    }

    public function search(User $user, string $term, int $limit): array
    {
        $query = Attachment::query()->visibleTo($user)->with('project');
        Like::any($query, ['original_name'], $term);

        return $query
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Attachment $file) => new SearchResult(
                type: 'file',
                id: $file->id,
                title: $file->original_name,
                subtitle: $file->project?->name,
                meta: ['file_type' => $file->type()->value, 'project_slug' => $file->project?->slug],
            ))
            ->all();
    }
}
