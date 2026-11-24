<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Safe "contains" search: the user's % and _ are treated as normal characters.
 * "!" is the escape character because it behaves the same in MySQL and SQLite.
 */
final class Like
{
    public static function pattern(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($term)).'%';
    }

    /**
     * Adds (col1 LIKE term OR col2 LIKE term ...) to the query.
     *
     * @param  Builder<*>  $query
     * @param  list<string>  $columns
     */
    public static function any(Builder $query, array $columns, string $term): void
    {
        $pattern = self::pattern($term);

        $query->where(function (Builder $inner) use ($columns, $pattern) {
            $grammar = $inner->getQuery()->getGrammar();

            foreach ($columns as $column) {
                $inner->orWhereRaw($grammar->wrap($inner->qualifyColumn($column))." LIKE ? ESCAPE '!'", [$pattern]);
            }
        });
    }
}
