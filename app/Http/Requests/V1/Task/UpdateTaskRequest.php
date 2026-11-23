<?php

namespace App\Http\Requests\V1\Task;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Http\Requests\Concerns\ValidatesTaskAssignees;
use App\Models\Task;
use App\Rules\ProjectMember;
use App\Rules\TeamAssignedToProject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Partial edit by the project's manager.
 */
class UpdateTaskRequest extends FormRequest
{
    use ValidatesTaskAssignees;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->task());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->task()->project;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'team_id' => ['sometimes', 'required', 'integer', new TeamAssignedToProject($project)],
            'status' => ['sometimes', 'required', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', 'required', Rule::enum(Priority::class)],
            'start_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'due_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'reviewer_id' => ['sometimes', 'nullable', 'integer', new ProjectMember($project)],
            ...$this->assigneeRules(),
        ];
    }

    /**
     * Checks that use the saved task for the fields that were not sent:
     *  - the due date is not before the start date,
     *  - the assignees are members of the (new or current) team.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $task = $this->task();

                if (! $validator->errors()->hasAny(['start_date', 'due_date'])) {
                    $start = $this->has('start_date') ? $this->dateInput('start_date') : $task->start_date;
                    $due = $this->has('due_date') ? $this->dateInput('due_date') : $task->due_date;

                    if ($start !== null && $due !== null && $due->lt($start)) {
                        $validator->errors()->add('due_date', __('The due date must be on or after the start date.'));
                    }
                }

                if ($validator->errors()->hasAny(['team_id', 'assignee_ids', 'assignee_ids.*'])) {
                    return;
                }

                if ($this->has('team_id') || $this->has('assignee_ids')) {
                    $teamId = $this->has('team_id') ? $this->integer('team_id') : $task->team_id;
                    $assignees = $this->has('assignee_ids')
                        ? $this->assigneeIds()
                        : $task->assignees()->pluck('users.id')->map(fn ($id) => (int) $id)->all();

                    $this->ensureAssigneesInTeam($validator, $teamId, $assignees);
                }
            },
        ];
    }

    public function task(): Task
    {
        /** @var Task */
        return $this->route('task');
    }

    private function dateInput(string $key): ?Carbon
    {
        return $this->filled($key) ? Carbon::parse($this->string($key)->toString()) : null;
    }
}
