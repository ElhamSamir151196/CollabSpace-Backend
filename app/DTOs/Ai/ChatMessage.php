<?php

namespace App\DTOs\Ai;

/**
 * One message sent to an AI provider. Role: system | user | assistant.
 */
final readonly class ChatMessage
{
    public function __construct(
        public string $role,
        public string $content,
    ) {}

    public static function system(string $content): self
    {
        return new self('system', $content);
    }

    public static function user(string $content): self
    {
        return new self('user', $content);
    }

    public static function assistant(string $content): self
    {
        return new self('assistant', $content);
    }
}
