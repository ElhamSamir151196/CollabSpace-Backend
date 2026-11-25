<?php

namespace App\Services\Ai;

use App\DTOs\Ai\AiResponse;
use App\DTOs\Ai\ChatMessage;
use App\Enums\AiModule;
use App\Exceptions\AiUnavailableException;
use App\Models\User;

/**
 * The single door to the AI for every feature:
 *  - refuses when the admin turned AI off (or the feature's data module),
 *  - calls the selected provider,
 *  - writes the AI activity log (feature, provider, model, tokens).
 */
class AiGateway
{
    public function __construct(
        private readonly AiManager $manager,
        private readonly AiSettings $settings,
    ) {}

    public function ensureEnabled(?AiModule $module = null): void
    {
        if (! $this->settings->enabled()) {
            throw AiUnavailableException::disabled();
        }

        if ($module !== null && ! $this->settings->allows($module)) {
            throw AiUnavailableException::moduleDisabled($module->label());
        }
    }

    /**
     * @param  list<ChatMessage>  $messages
     * @param  array<string, mixed>|null  $jsonSchema
     */
    public function generate(User $user, string $feature, array $messages, ?array $jsonSchema = null, ?AiModule $module = null): AiResponse
    {
        $this->ensureEnabled($module);

        $response = $this->manager->driver()->generate($messages, $jsonSchema);

        activity('ai')->causedBy($user)->event($feature)
            ->withProperties([
                'provider' => $response->provider,
                'model' => $response->model,
                'input_tokens' => $response->inputTokens,
                'output_tokens' => $response->outputTokens,
            ])
            ->log('AI request');

        return $response;
    }
}
