<?php

namespace App\Services\Search\Providers;

use App\DTOs\Search\SearchResult;
use App\Interfaces\Search\SearchProvider;
use App\Models\User;
use App\Support\Like;

/**
 * People (active users only). Emails are matched but never shown.
 */
class UserSearch implements SearchProvider
{
    public function key(): string
    {
        return 'people';
    }

    public function search(User $user, string $term, int $limit): array
    {
        $query = User::query()->where('is_active', true);
        Like::any($query, ['name', 'email', 'job_title'], $term);

        return $query
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (User $person) => new SearchResult(
                type: 'user',
                id: $person->id,
                title: $person->name,
                subtitle: $person->job_title,
                meta: ['avatar_url' => $person->avatarUrl()],
            ))
            ->all();
    }
}
