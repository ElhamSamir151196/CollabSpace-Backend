<?php

namespace App\Services\Search\Providers;

use App\DTOs\Search\SearchResult;
use App\Interfaces\Search\SearchProvider;
use App\Models\Project;
use App\Models\User;
use App\Support\Like;

class ProjectSearch implements SearchProvider
{
    public function key(): string
    {
        return 'projects';
    }

    public function search(User $user, string $term, int $limit): array
    {
        $query = Project::query()->visibleTo($user);
        Like::any($query, ['name', 'description', 'type'], $term);

        return $query
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Project $project) => new SearchResult(
                type: 'project',
                id: $project->id,
                title: $project->name,
                subtitle: $project->status->label(),
                meta: ['slug' => $project->slug],
            ))
            ->all();
    }
}
