<?php

namespace App\Actions\Ai;

use App\DTOs\Ai\ChatMessage;
use App\Enums\AiMessageRole;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use App\Services\Ai\AiContextBuilder;
use App\Services\Ai\AiGateway;
use App\Services\Ai\AiPrompts;
use Illuminate\Support\Str;
use Throwable;

/**
 * One turn of the AI chat: save the question, send it with the recent
 * history and the user's workspace data, save the answer.
 * If the AI fails, the question is removed again so the chat stays clean.
 */
class SendAiMessageAction
{
    public const DEFAULT_TITLE = 'New chat';

    public function __construct(
        private readonly AiGateway $ai,
        private readonly AiContextBuilder $context,
    ) {}

    /**
     * @return array{question: AiMessage, answer: AiMessage}
     */
    public function handle(AiConversation $conversation, string $content, User $user): array
    {
        $this->ai->ensureEnabled();

        $question = $conversation->messages()->create(['role' => AiMessageRole::User, 'content' => $content]);

        try {
            $response = $this->ai->generate($user, 'chat', [
                AiPrompts::assistant($user),
                AiPrompts::workspaceData($this->context->forUser($user)),
                ...$this->history($conversation),
            ]);
        } catch (Throwable $e) {
            $question->delete();

            throw $e;
        }

        $answer = $conversation->messages()->create([
            'role' => AiMessageRole::Assistant,
            'content' => $response->content,
            'provider' => $response->provider,
            'model' => $response->model,
            'input_tokens' => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
        ]);

        $conversation->forceFill([
            'last_message_at' => now(),
            'title' => $conversation->title === self::DEFAULT_TITLE ? Str::limit($content, 60) : $conversation->title,
        ])->save();

        return ['question' => $question, 'answer' => $answer];
    }

    /**
     * The last messages of the conversation (including the new question), oldest first.
     *
     * @return list<ChatMessage>
     */
    private function history(AiConversation $conversation): array
    {
        return $conversation->messages()
            ->latest('id')
            ->limit((int) config('ai.context.history_messages'))
            ->get()
            ->reverse()
            ->map(fn (AiMessage $message) => new ChatMessage($message->role->value, $message->content))
            ->values()
            ->all();
    }
}
