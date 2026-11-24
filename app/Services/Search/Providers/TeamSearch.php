<?php

namespace App\Services\Search\Providers;

use App\DTOs\Search\SearchResult;
use App\Interfaces\Search\SearchProvider;
use App\Models\Team;
use App\Models\User;
use App\Support\Like;

/**
 * Teams are visible to every signed-in user.
 */
class TeamSearch implements SearchProvider
{
    public function key(): string
    {
        return 'teams';
    }

    public function search(User $user, string $term, int $limit): array
    {
        $query = Team::query()->withCount('members');
        Like::any($query, ['name', 'description'], $term);

        return $query
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Team $team) => new SearchResult(
                type: 'team',
                id: $team->id,
                title: $team->name,
                subtitle: trans_choice(':count member|:count members', (int) $team->members_count),
                meta: ['color' => $team->color],
            ))
            ->all();
    }
}
