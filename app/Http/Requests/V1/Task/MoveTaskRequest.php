<?php

namespace App\Http\Requests\V1\Task;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Kanban drag & drop: { status, position? }.
 * position = index in the target column (0 = top). Omit it to drop at the bottom.
 */
class MoveTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Task $task */
        $task = $this->route('task');

        return (bool) $this->user()?->can('move', [$task, $this->targetStatus()]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'position' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    /**
     * Read before validation (authorize runs first), so it can be null.
     */
    public function targetStatus(): ?TaskStatus
    {
        $status = $this->input('status');

        return is_string($status) ? TaskStatus::tryFrom($status) : null;
    }

    public function position(): ?int
    {
        return $this->filled('position') ? $this->integer('position') : null;
    }
}
