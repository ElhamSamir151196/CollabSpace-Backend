<?php

namespace App\Filters;

/**
 * ?search=  ?member_id=  ?sort=name|-created_at|-members_count ...
 */
class TeamFilter extends QueryFilter
{
    protected array $sortable = ['name', 'created_at', 'members_count'];

    /** Comes from withCount('members') in the repository. */
    protected array $aliasSortable = ['members_count'];

    protected string $defaultSort = 'name';

    public function search(string $term): void
    {
        $this->whereLike(['name', 'description'], $term);
    }

    public function memberId(int|string $userId): void
    {
        $this->builder->whereHas('members', fn ($query) => $query->whereKey((int) $userId));
    }
}
