<?php

namespace App\Interfaces\Ai;

use App\DTOs\Ai\AiResponse;
use App\DTOs\Ai\ChatMessage;
use App\Exceptions\AiUnavailableException;

/**
 * One AI vendor (Gemini, OpenAI, Claude ...). The rest of the app only knows
 * this interface; AiManager decides which implementation is used.
 */
interface AiProvider
{
    /**
     * Short name stored with each answer, e.g. "gemini".
     */
    public function name(): string;

    public function model(): string;

    /**
     * @param  list<ChatMessage>  $messages  conversation, system messages first
     * @param  array<string, mixed>|null  $jsonSchema  when set, the answer must be JSON matching it
     *
     * @throws AiUnavailableException when the provider fails
     */
    public function generate(array $messages, ?array $jsonSchema = null): AiResponse;
}
