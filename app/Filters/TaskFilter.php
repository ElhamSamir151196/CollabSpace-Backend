<?php

namespace App\Filters;

use App\Enums\TaskStatus;

/**
 * ?search= ?project_id= ?team_id= ?status= ?priority= ?assignee_id= ?reviewer_id=
 * ?due_from= ?due_to= ?overdue=1 ?sort=position|-due_date ...
 */
class TaskFilter extends QueryFilter
{
    protected array $sortable = ['position', 'due_date', 'created_at', 'title', 'progress'];

    protected string $defaultSort = '-created_at';

    public function search(string $term): void
    {
        $this->whereLike(['title', 'description'], $term);
    }

    public function projectId(int|string $projectId): void
    {
        $this->builder->where('project_id', (int) $projectId);
    }

    public function teamId(int|string $teamId): void
    {
        $this->builder->where('team_id', (int) $teamId);
    }

    public function status(string $status): void
    {
        $this->builder->where('status', $status);
    }

    public function priority(string $priority): void
    {
        $this->builder->where('priority', $priority);
    }

    public function assigneeId(int|string $userId): void
    {
        $this->builder->whereHas('assignees', fn ($query) => $query->whereKey((int) $userId));
    }

    public function reviewerId(int|string $userId): void
    {
        $this->builder->where('reviewer_id', (int) $userId);
    }

    public function dueFrom(string $date): void
    {
        $this->builder->whereDate('due_date', '>=', $date);
    }

    public function dueTo(string $date): void
    {
        $this->builder->whereDate('due_date', '<=', $date);
    }

    public function overdue(bool|int|string $overdue): void
    {
        if (! filter_var($overdue, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $this->builder
            ->whereDate('due_date', '<', today())
            ->where('status', '!=', TaskStatus::Completed->value);
    }
}
