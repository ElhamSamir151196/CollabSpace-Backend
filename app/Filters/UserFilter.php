<?php

namespace App\Filters;

/**
 * ?search=  ?role=  ?is_active=  ?availability_status=  ?sort=name|-created_at ...
 */
class UserFilter extends QueryFilter
{
    protected array $sortable = ['name', 'email', 'created_at', 'last_seen_at'];

    protected string $defaultSort = 'name';

    public function search(string $term): void
    {
        $this->whereLike(['name', 'email', 'job_title'], $term);
    }

    public function role(string $role): void
    {
        $this->builder->whereHas('roles', fn ($query) => $query->where('name', $role));
    }

    public function isActive(bool|int|string $isActive): void
    {
        $this->builder->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
    }

    public function availabilityStatus(string $status): void
    {
        $this->builder->where('availability_status', $status);
    }
}
