<?php

namespace App\Http\Requests\V1\Chat;

use App\Models\Conversation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * { message_id? } — omit it to mark everything as read.
 */
class MarkConversationReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('view', $this->conversation());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message_id' => [
                'nullable', 'integer',
                Rule::exists('messages', 'id')->where('conversation_id', $this->conversation()->id),
            ],
        ];
    }

    public function conversation(): Conversation
    {
        /** @var Conversation */
        return $this->route('conversation');
    }
}
