<?php

namespace App\Http\Requests\V1\Chat;

use App\Http\Requests\Concerns\ValidatesMentions;
use App\Models\Message;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Edit a message's text (files can't be changed; delete and resend instead).
 */
class UpdateMessageRequest extends FormRequest
{
    use ValidatesMentions;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->message());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            ...$this->mentionRules(),
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->ensureMentionsCanReadConversation($validator, $this->message()->conversation),
        ];
    }

    public function message(): Message
    {
        /** @var Message */
        return $this->route('message');
    }
}
