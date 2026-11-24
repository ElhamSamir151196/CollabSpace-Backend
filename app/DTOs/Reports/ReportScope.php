<?php

namespace App\DTOs\Reports;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * What a report / the dashboard looks at:
 *  - projectIds: the projects included (null = every project, for admins),
 *  - from / to:  the period (charts are monthly inside it).
 */
final readonly class ReportScope
{
    /**
     * @param  list<int>|null  $projectIds
     */
    public function __construct(
        public ?array $projectIds,
        public CarbonImmutable $from,
        public CarbonImmutable $to,
    ) {}

    /**
     * Limits a query on a table that has a project_id column.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function applyTo(Builder $query, string $column = 'project_id'): Builder
    {
        return $this->projectIds === null
            ? $query
            : $query->whereIn($query->qualifyColumn($column), $this->projectIds);
    }

    /**
     * Months of the period as "2025-07", "2025-08" ...
     *
     * @return list<string>
     */
    public function months(): array
    {
        $months = [];

        for ($month = $this->from->startOfMonth(); $month->lte($this->to); $month = $month->addMonth()) {
            $months[] = $month->format('Y-m');
        }

        return $months;
    }

    /**
     * The same length of time just before this period (for "+9% from last period").
     */
    public function previous(): self
    {
        $days = (int) $this->from->diffInDays($this->to) + 1;

        return new self($this->projectIds, $this->from->subDays($days), $this->from->subDay()->endOfDay());
    }
}
