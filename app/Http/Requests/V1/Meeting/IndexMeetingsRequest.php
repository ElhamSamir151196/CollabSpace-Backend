<?php

namespace App\Http\Requests\V1\Meeting;

use App\Enums\MeetingStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Calendar: ?from=2025-12-15&to=2025-12-21 (defaults to the current week).
 * Dashboard "Up coming meetings": ?upcoming=1&mine=1&limit=3.
 */
class IndexMeetingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'upcoming' => ['nullable', 'boolean'],
            'project_id' => ['nullable', 'integer'],
            'mine' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::enum(MeetingStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $filters = $this->safe()->except(['mine', 'limit']);

        if ($this->boolean('mine')) {
            $filters['participant_id'] = $this->user()?->id;
        }

        $hasRange = $this->filled('from') || $this->filled('to') || $this->boolean('upcoming');

        if (! $hasRange) {
            $filters['from'] = now()->startOfWeek()->toDateString();
            $filters['to'] = now()->endOfWeek()->toDateString();
        }

        return $filters;
    }
}
