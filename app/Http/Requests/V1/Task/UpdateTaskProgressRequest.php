<?php

namespace App\Http\Requests\V1\Task;

use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Task $task */
        $task = $this->route('task');

        return (bool) $this->user()?->can('updateProgress', $task);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }
}
