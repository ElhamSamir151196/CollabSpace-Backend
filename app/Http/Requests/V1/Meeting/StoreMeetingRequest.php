<?php

namespace App\Http\Requests\V1\Meeting;

use App\Http\Requests\Concerns\ValidatesMeetingSchedule;
use App\Models\Meeting;
use App\Models\Project;
use App\Rules\ProjectMember;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

/**
 * New Meeting form: subject, project, date / time / duration, guests,
 * note, link and an optional cover image (multipart "cover").
 */
class StoreMeetingRequest extends FormRequest
{
    use ValidatesMeetingSchedule;

    private ?Project $resolvedProject = null;

    public function authorize(): bool
    {
        $project = $this->project();

        // Unknown project: let validation answer with 422 instead of 403.
        return $project === null || (bool) $this->user()?->can('create', [Meeting::class, $project]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->project();

        return [
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'meeting_link' => ['nullable', 'url:http,https', 'max:500'],
            ...$this->scheduleRules(),
            'participant_ids' => ['sometimes', 'array', 'max:100'],
            'participant_ids.*' => array_filter(['integer', 'distinct', $project !== null ? new ProjectMember($project) : null]),
            'cover' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max((int) config('collabspace.meetings.cover_max_kb'))],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->ensureStartsInFuture($validator)];
    }

    public function project(): ?Project
    {
        if ($this->resolvedProject === null && is_numeric($this->input('project_id'))) {
            $this->resolvedProject = Project::query()->find((int) $this->input('project_id'));
        }

        return $this->resolvedProject;
    }

    /**
     * @return list<int>
     */
    public function participantIds(): array
    {
        return array_values(array_unique(array_map('intval', (array) $this->validated('participant_ids', []))));
    }
}
