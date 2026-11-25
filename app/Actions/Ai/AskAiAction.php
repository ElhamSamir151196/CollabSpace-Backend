<?php

namespace App\Actions\Ai;

use App\DTOs\Ai\AiResponse;
use App\DTOs\Ai\ChatMessage;
use App\Models\Project;
use App\Models\User;
use App\Services\Ai\AiContextBuilder;
use App\Services\Ai\AiGateway;
use App\Services\Ai\AiPrompts;

/**
 * The "Ask AI" widget on the Dashboard: one question, one answer, nothing stored
 * (except the AI activity log). Optionally focused on one project.
 */
class AskAiAction
{
    public function __construct(
        private readonly AiGateway $ai,
        private readonly AiContextBuilder $context,
    ) {}

    public function handle(User $user, string $question, ?Project $project = null): AiResponse
    {
        $this->ai->ensureEnabled();

        return $this->ai->generate($user, 'ask', [
            AiPrompts::assistant($user),
            AiPrompts::workspaceData($this->context->forUser($user, $project)),
            ChatMessage::user($question),
        ]);
    }
}
