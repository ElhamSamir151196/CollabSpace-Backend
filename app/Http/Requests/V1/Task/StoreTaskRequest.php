<?php

namespace App\Http\Requests\V1\Task;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Http\Requests\Concerns\ValidatesTaskAssignees;
use App\Models\Project;
use App\Models\Task;
use App\Rules\ProjectMember;
use App\Rules\TeamAssignedToProject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create Task form: POST /projects/{project}/tasks.
 * Files are attached with the Files module.
 */
class StoreTaskRequest extends FormRequest
{
    use ValidatesTaskAssignees;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', [Task::class, $this->project()]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'team_id' => ['required', 'integer', new TeamAssignedToProject($this->project())],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(Priority::class)],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'reviewer_id' => ['nullable', 'integer', new ProjectMember($this->project())],
            ...$this->assigneeRules(),
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['team_id', 'assignee_ids', 'assignee_ids.*'])) {
                    return;
                }

                $this->ensureAssigneesInTeam($validator, $this->integer('team_id'), $this->assigneeIds());
            },
        ];
    }

    public function project(): Project
    {
        /** @var Project */
        return $this->route('project');
    }
}
