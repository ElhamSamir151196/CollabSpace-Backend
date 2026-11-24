<?php

namespace App\Support;

/**
 * Small math helpers shared by the dashboard and the reports.
 */
final class Metrics
{
    /**
     * part / total as a 0-100 percentage (0 when total is 0).
     */
    public static function rate(int|float $part, int|float $total): int
    {
        return $total > 0 ? (int) round($part / $total * 100) : 0;
    }

    /**
     * Change between two counts in % ("+9% from last month").
     * Null when there is nothing to compare with.
     */
    public static function percentChange(int|float $current, int|float $previous): ?int
    {
        if ($previous == 0) {
            return $current == 0 ? 0 : null;
        }

        return (int) round(($current - $previous) / $previous * 100);
    }

    /**
     * { value, previous, change } for rates, where change is in points (70% -> 73% = +3).
     *
     * @return array{value: int, previous: int, change: int}
     */
    public static function rateWithChange(int $value, int $previous): array
    {
        return ['value' => $value, 'previous' => $previous, 'change' => $value - $previous];
    }
}
