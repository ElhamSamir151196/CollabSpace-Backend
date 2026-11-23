<?php

namespace App\Enums\Concerns;

/**
 * Shared helpers for string-backed enums.
 *
 * Every enum that uses this trait must implement label().
 */
trait HasEnumHelpers
{
    abstract public function label(): string;

    /**
     * All raw values — handy for validation: Rule::in(TaskStatus::values()).
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Value/label pairs for the frontend dropdowns.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    /**
     * Shape used inside API resources: {"value": "pending", "label": "To-do"}.
     *
     * @return array{value: string, label: string}
     */
    public function toArray(): array
    {
        return ['value' => $this->value, 'label' => $this->label()];
    }
}
