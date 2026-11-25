<?php

namespace App\Filters;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * ?search=  ?log=  ?event=  ?causer_id=  ?subject_type=  ?subject_id=  ?from=  ?to=  ?sort=-created_at
 */
class ActivityFilter extends QueryFilter
{
    protected array $sortable = ['created_at'];

    protected string $defaultSort = '-created_at';

    public function search(string $term): void
    {
        $this->whereLike(['description', 'event'], $term);
    }

    public function log(string $log): void
    {
        $this->builder->where('log_name', $log);
    }

    public function event(string $event): void
    {
        $this->builder->where('event', $event);
    }

    public function causerId(int|string $userId): void
    {
        $this->builder->where('causer_type', (new User)->getMorphClass())->where('causer_id', (int) $userId);
    }

    public function subjectType(string $alias): void
    {
        $this->builder->where('subject_type', Activity::SUBJECT_TYPES[$alias] ?? $alias);
    }

    public function subjectId(int|string $id): void
    {
        $this->builder->where('subject_id', (int) $id);
    }

    public function from(string $date): void
    {
        $this->builder->where('created_at', '>=', Carbon::parse($date)->startOfDay());
    }

    public function to(string $date): void
    {
        $this->builder->where('created_at', '<=', Carbon::parse($date)->endOfDay());
    }
}
