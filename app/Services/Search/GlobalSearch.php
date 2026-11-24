<?php

namespace App\Services\Search;

use App\DTOs\Search\SearchResult;
use App\Interfaces\Search\SearchProvider;
use App\Models\User;
use Illuminate\Container\Attributes\Tag;

/**
 * The search box in the top bar: asks every registered provider and returns
 * the results grouped by section. Each provider filters by permissions itself.
 */
class GlobalSearch
{
    /**
     * @param  iterable<SearchProvider>  $providers
     */
    public function __construct(
        #[Tag('search.providers')] private readonly iterable $providers,
    ) {}

    /**
     * @return list<string>
     */
    public function sections(): array
    {
        $keys = [];

        foreach ($this->providers as $provider) {
            $keys[] = $provider->key();
        }

        return $keys;
    }

    /**
     * @param  list<string>|null  $only  section keys to search (null = all)
     * @return array<string, list<SearchResult>>
     */
    public function search(User $user, string $term, ?array $only = null, int $limitPerSection = 5): array
    {
        $results = [];

        foreach ($this->providers as $provider) {
            if ($only !== null && ! in_array($provider->key(), $only, true)) {
                continue;
            }

            $results[$provider->key()] = $provider->search($user, $term, $limitPerSection);
        }

        return $results;
    }
}
