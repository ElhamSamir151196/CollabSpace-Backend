<?php

namespace App\Filters;

use App\Enums\ProjectStatus;

/**
 * ?search= ?status= ?priority= ?manager_id= ?team_id=
 * ?deadline_from= ?deadline_to= ?overdue=1 ?sort=-deadline ...
 */
class ProjectFilter extends QueryFilter
{
    protected array $sortable = ['name', 'created_at', 'start_date', 'deadline'];

    protected string $defaultSort = '-created_at';

    public function search(string $term): void
    {
        $this->whereLike(['name', 'description', 'type'], $term);
    }

    public function status(string $status): void
    {
        $this->builder->where('status', $status);
    }

    public function priority(string $priority): void
    {
        $this->builder->where('priority', $priority);
    }

    public function managerId(int|string $userId): void
    {
        $this->builder->where('manager_id', (int) $userId);
    }

    public function teamId(int|string $teamId): void
    {
        $this->builder->whereHas('teams', fn ($query) => $query->whereKey((int) $teamId));
    }

    public function deadlineFrom(string $date): void
    {
        $this->builder->whereDate('deadline', '>=', $date);
    }

    public function deadlineTo(string $date): void
    {
        $this->builder->whereDate('deadline', '<=', $date);
    }

    public function overdue(bool|int|string $overdue): void
    {
        if (! filter_var($overdue, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $this->builder
            ->whereDate('deadline', '<', today())
            ->whereNotIn('status', [ProjectStatus::Completed->value, ProjectStatus::Cancelled->value]);
    }
}
