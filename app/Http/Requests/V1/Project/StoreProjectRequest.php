<?php

namespace App\Http\Requests\V1\Project;

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Http\Requests\Concerns\ValidatesProjectAssignments;
use App\Http\Requests\Concerns\ValidatesUploadedFiles;
use App\Models\Project;
use App\Rules\EligibleProjectManager;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * New Project form: name, description, type, priority, status,
 * start date, deadline, teams, guests and files (multipart files[]).
 */
class StoreProjectRequest extends FormRequest
{
    use ValidatesProjectAssignments, ValidatesUploadedFiles;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Project::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'max:50'],
            'priority' => ['required', Rule::enum(Priority::class)],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'deadline' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'manager_id' => [
                'nullable', 'integer',
                Rule::prohibitedIf(fn () => ! $this->user()?->can('changeManager', Project::class)),
                new EligibleProjectManager,
            ],
            ...$this->teamIdsRules(required: false),
            ...$this->guestIdsRules(required: false),
            ...$this->fileRules(required: false),
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
}
