<?php

namespace App\Http\Requests\V1\Project;

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Rules\EligibleProjectManager;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Partial update of the project details.
 * Teams and guests have their own endpoints.
 */
class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->project();

        return $project !== null && (bool) $this->user()?->can('update', $project);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'priority' => ['sometimes', 'required', Rule::enum(Priority::class)],
            'status' => ['sometimes', 'required', Rule::enum(ProjectStatus::class)],
            'start_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'deadline' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'manager_id' => [
                'sometimes', 'required', 'integer',
                Rule::prohibitedIf(fn () => ! $this->user()?->can('changeManager', Project::class)),
                new EligibleProjectManager,
            ],
        ];
    }

    /**
     * The deadline can't be before the start date, even when only one
     * of them is sent (the other one comes from the saved project).
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $project = $this->project();

                if ($project === null || $validator->errors()->hasAny(['start_date', 'deadline'])) {
                    return;
                }

                $start = $this->filled('start_date') ? Carbon::parse($this->string('start_date')->toString()) : $project->start_date;
                $deadline = $this->filled('deadline') ? Carbon::parse($this->string('deadline')->toString()) : $project->deadline;

                if ($deadline->lt($start)) {
                    $validator->errors()->add('deadline', __('The deadline must be on or after the start date.'));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'manager_id.prohibited' => __('Only an admin can choose another manager for the project.'),
        ];
    }

    private function project(): ?Project
    {
        $project = $this->route('project');

        return $project instanceof Project ? $project : null;
    }
}
