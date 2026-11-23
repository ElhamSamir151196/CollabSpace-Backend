<?php

namespace App\DTOs\Auth;

use App\Http\Requests\V1\Auth\RegisterRequest;

final readonly class RegisterData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $deviceName = null,
    ) {}

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            name: $request->string('name')->trim()->toString(),
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            deviceName: $request->filled('device_name') ? $request->string('device_name')->toString() : null,
        );
    }
}
