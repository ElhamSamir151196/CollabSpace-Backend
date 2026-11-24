<?php

namespace App\Http\Requests\V1\Files;

use App\Enums\FileType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAttachmentsRequest extends FormRequest
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
            'type' => ['nullable', Rule::enum(FileType::class)],
            'source' => ['nullable', Rule::in(['project', 'task', 'message'])],
            'uploaded_by' => ['nullable', 'integer'],
            'sort' => ['nullable', Rule::in(['created_at', '-created_at', 'original_name', '-original_name', 'size', '-size'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->safe()->except(['per_page']);
    }
}
