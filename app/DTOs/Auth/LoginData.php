<?php

namespace App\DTOs\Auth;

use App\Http\Requests\V1\Auth\LoginRequest;

final readonly class LoginData
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember = false,
        public ?string $deviceName = null,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            remember: $request->boolean('remember'),
            deviceName: $request->filled('device_name') ? $request->string('device_name')->toString() : null,
        );
    }
}
