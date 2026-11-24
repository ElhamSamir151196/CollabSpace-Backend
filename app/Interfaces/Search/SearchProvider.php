<?php

namespace App\Interfaces\Search;

use App\DTOs\Search\SearchResult;
use App\Models\User;

/**
 * One section of the global search (projects, tasks, people ...).
 * Every provider must only return what the user is allowed to see.
 * Register new providers in AppServiceProvider (tag "search.providers").
 */
interface SearchProvider
{
    /**
     * Key used in the response and in ?types= (e.g. "projects").
     */
    public function key(): string;

    /**
     * @return list<SearchResult>
     */
    public function search(User $user, string $term, int $limit): array;
}
