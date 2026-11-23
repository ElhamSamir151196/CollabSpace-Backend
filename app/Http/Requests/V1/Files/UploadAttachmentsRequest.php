<?php

namespace App\Http\Requests\V1\Files;

use App\Http\Requests\Concerns\ValidatesUploadedFiles;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * multipart/form-data with files[] — used for both
 * POST /projects/{project}/files and POST /tasks/{task}/files.
 */
class UploadAttachmentsRequest extends FormRequest
{
    use ValidatesUploadedFiles;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('upload', [Attachment::class, $this->target()]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->fileRules(required: true);
    }

    /**
     * The task when uploading to a task, otherwise the project.
     */
    public function target(): Project|Task
    {
        $task = $this->route('task');

        if ($task instanceof Task) {
            return $task;
        }

        /** @var Project */
        return $this->route('project');
    }
}
