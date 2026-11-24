<?php

namespace App\Actions\Search;

use App\Models\SearchHistory;
use App\Models\User;

/**
 * Saves a search in "Recent Searches": the same text moves back to the top
 * instead of being duplicated, and only the last few are kept per user.
 */
class RecordSearchAction
{
    public const KEEP = 10;

    public function handle(User $user, string $query): void
    {
        $query = mb_substr(trim($query), 0, 100);

        $entry = SearchHistory::query()->firstOrNew(['user_id' => $user->id, 'query' => $query]);

        // Searching the same text again moves it back to the top.
        $entry->exists ? $entry->touch() : $entry->save();

        $keep = $user->searchHistories()->limit(self::KEEP)->pluck('id');

        SearchHistory::query()->where('user_id', $user->id)->whereNotIn('id', $keep)->delete();
    }
}
