<?php

namespace App\DTOs\Ai;

/**
 * The same answer shape whatever provider produced it.
 * "structured" is the decoded JSON when a JSON schema was requested.
 */
final readonly class AiResponse
{
    /**
     * @param  array<string, mixed>|null  $structured
     */
    public function __construct(
        public string $content,
        public ?array $structured,
        public string $provider,
        public string $model,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
    ) {}
}
