<?php

namespace App\Services\Search\Providers;

use App\DTOs\Search\SearchResult;
use App\Interfaces\Search\SearchProvider;
use App\Models\Meeting;
use App\Models\User;
use App\Support\Like;

class MeetingSearch implements SearchProvider
{
    public function key(): string
    {
        return 'meetings';
    }

    public function search(User $user, string $term, int $limit): array
    {
        $query = Meeting::query()->visibleTo($user)->with('project');
        Like::any($query, ['title', 'description'], $term);

        return $query
            ->orderByDesc('start_at')
            ->limit($limit)
            ->get()
            ->map(fn (Meeting $meeting) => new SearchResult(
                type: 'meeting',
                id: $meeting->id,
                title: $meeting->title,
                subtitle: $meeting->project->name.' · '.$meeting->start_at->format('M j, H:i'),
                meta: ['start_at' => $meeting->start_at->toIso8601String(), 'state' => $meeting->state()],
            ))
            ->all();
    }
}
