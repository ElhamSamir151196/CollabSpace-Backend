<?php

namespace App\Http\Requests\V1\Task;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filters shared by the tasks list and the Kanban board.
 */
class IndexTasksRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'project_id' => ['nullable', 'integer'],
            'team_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'assignee_id' => ['nullable', 'integer'],
            'reviewer_id' => ['nullable', 'integer'],
            'mine' => ['nullable', 'boolean'],
            'due_from' => ['nullable', 'date_format:Y-m-d'],
            'due_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:due_from'],
            'overdue' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in([
                'position', '-position', 'due_date', '-due_date', 'created_at', '-created_at',
                'title', '-title', 'progress', '-progress',
            ])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Filters for TaskFilter. "mine=1" (My Tasks) becomes assignee_id = the current user.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $filters = $this->safe()->except(['mine', 'per_page']);

        if ($this->boolean('mine')) {
            $filters['assignee_id'] = $this->user()?->id;
        }

        return $filters;
    }
}
