<?php

namespace App\DTOs\Auth;

use Carbon\CarbonInterface;

final readonly class IssuedToken
{
    public function __construct(
        public string $plainText,
        public CarbonInterface $expiresAt,
    ) {}

    /**
     * @return array{access_token: string, token_type: string, expires_at: string}
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->plainText,
            'token_type' => 'Bearer',
            'expires_at' => $this->expiresAt->toIso8601String(),
        ];
    }
}
