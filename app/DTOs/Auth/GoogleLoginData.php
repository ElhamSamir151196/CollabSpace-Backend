<?php

namespace App\DTOs\Auth;

use App\Http\Requests\V1\Auth\GoogleLoginRequest;

final readonly class GoogleLoginData
{
    public function __construct(
        public string $accessToken,
        public bool $remember = false,
        public ?string $deviceName = null,
    ) {}

    public static function fromRequest(GoogleLoginRequest $request): self
    {
        return new self(
            accessToken: $request->string('access_token')->toString(),
            remember: $request->boolean('remember'),
            deviceName: $request->filled('device_name') ? $request->string('device_name')->toString() : null,
        );
    }
}
