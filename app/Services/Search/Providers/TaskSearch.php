<?php

namespace App\Services\Search\Providers;

use App\DTOs\Search\SearchResult;
use App\Interfaces\Search\SearchProvider;
use App\Models\Task;
use App\Models\User;
use App\Support\Like;

class TaskSearch implements SearchProvider
{
    public function key(): string
    {
        return 'tasks';
    }

    public function search(User $user, string $term, int $limit): array
    {
        $query = Task::query()->visibleTo($user)->with('project');
        Like::any($query, ['title', 'description'], $term);

        return $query
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (Task $task) => new SearchResult(
                type: 'task',
                id: $task->id,
                title: $task->title,
                subtitle: $task->project->name.' · '.$task->status->label(),
                meta: ['project_slug' => $task->project->slug, 'status' => $task->status->value],
            ))
            ->all();
    }
}
