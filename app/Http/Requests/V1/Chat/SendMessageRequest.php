<?php

namespace App\Http\Requests\V1\Chat;

use App\Http\Requests\Concerns\ValidatesMentions;
use App\Http\Requests\Concerns\ValidatesUploadedFiles;
use App\Models\Conversation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * POST /conversations/{conversation}/messages
 * { body?, parent_id?, mention_ids[]?, files[]? } — text, files, or both.
 */
class SendMessageRequest extends FormRequest
{
    use ValidatesMentions, ValidatesUploadedFiles;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('sendMessage', $this->conversation());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required_without:files', 'nullable', 'string', 'max:5000'],
            // Threads are one level deep: replies go to a top-level message of the same chat.
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('messages', 'id')
                    ->where('conversation_id', $this->conversation()->id)
                    ->whereNull('parent_id')
                    ->whereNull('deleted_at'),
            ],
            ...$this->mentionRules(),
            ...$this->fileRules(required: false),
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->ensureMentionsCanReadConversation($validator, $this->conversation()),
        ];
    }

    public function conversation(): Conversation
    {
        /** @var Conversation */
        return $this->route('conversation');
    }
}
