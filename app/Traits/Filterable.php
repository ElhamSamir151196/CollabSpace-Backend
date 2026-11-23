<?php

namespace App\Traits;

use App\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Add to any model that supports filtering:
 *
 *   class Task extends Model { use Filterable; }
 *   Task::query()->filter(new TaskFilter($filters));
 */
trait Filterable
{
    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function filter(Builder $query, QueryFilter $filter): void
    {
        $filter->apply($query);
    }
}
