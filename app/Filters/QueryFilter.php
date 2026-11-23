<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionMethod;

/**
 * Base class for all model filters (ProjectFilter, TaskFilter ...).
 *
 * How it works:
 *  - Every PUBLIC method declared in the child class is a filter.
 *    The input key is snake_case, the method is camelCase:
 *      ?project_id=5  ->  projectId(5)
 *  - Keys that have no matching public method are ignored (whitelist),
 *    so a client can never filter by a column we did not allow.
 *  - Empty values (null / '') are skipped.
 *  - Sorting: ?sort=name (asc) or ?sort=-created_at (desc),
 *    only for columns listed in $sortable.
 *
 * Usage:
 *   Task::query()->filter(new TaskFilter($request->validated()))->paginate();
 */
abstract class QueryFilter
{
    /**
     * The query being filtered. Child filters work on any model, so the
     * model type is left open (star projection).
     *
     * @var Builder<*>
     */
    protected Builder $builder;

    /**
     * Columns the client may sort by.
     *
     * @var list<string>
     */
    protected array $sortable = ['created_at'];

    /** Used when the request has no "sort". Prefix "-" means descending. */
    protected string $defaultSort = '-created_at';

    /**
     * @param  array<string, mixed>  $filters  Usually $request->validated()
     */
    public function __construct(protected array $filters = []) {}

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->filters as $key => $value) {
            if ($key === 'sort' || $value === null || $value === '') {
                continue;
            }

            $method = Str::camel((string) $key);

            if ($this->isFilterMethod($method)) {
                $this->{$method}($value);
            }
        }

        $this->applySort();

        return $builder;
    }

    /**
     * LIKE search on one or more columns, with the user's wildcards escaped
     * so "%" or "_" in the search term are literal. "!" is used as the escape
     * character because it behaves the same in MySQL and SQLite (tests).
     *
     * @param  list<string>  $columns
     */
    protected function whereLike(array $columns, string $term): void
    {
        $escaped = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($term)).'%';

        $this->builder->where(function (Builder $query) use ($columns, $escaped) {
            $grammar = $query->getQuery()->getGrammar();

            foreach ($columns as $column) {
                $query->orWhereRaw(
                    $grammar->wrap($query->qualifyColumn($column))." LIKE ? ESCAPE '!'",
                    [$escaped],
                );
            }
        });
    }

    private function applySort(): void
    {
        $sort = (string) ($this->filters['sort'] ?? $this->defaultSort);
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if (! in_array($column, $this->sortable, true)) {
            $column = ltrim($this->defaultSort, '-');
            $direction = str_starts_with($this->defaultSort, '-') ? 'desc' : 'asc';
        }

        $this->builder->orderBy($this->builder->qualifyColumn($column), $direction);
    }

    private function isFilterMethod(string $method): bool
    {
        if (! method_exists($this, $method)) {
            return false;
        }

        $reflection = new ReflectionMethod($this, $method);

        return $reflection->isPublic()
            && ! $reflection->isStatic()
            && $reflection->getDeclaringClass()->getName() !== self::class;
    }
}
