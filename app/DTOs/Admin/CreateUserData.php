<?php

namespace App\DTOs\Admin;

use App\Enums\UserRole;
use App\Http\Requests\V1\Admin\User\StoreUserRequest;

final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public UserRole $role,
        public ?string $phone = null,
        public ?string $jobTitle = null,
        public ?int $yearsOfExperience = null,
    ) {}

    public static function fromRequest(StoreUserRequest $request): self
    {
        return new self(
            name: $request->string('name')->trim()->toString(),
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            role: $request->enum('role', UserRole::class) ?? UserRole::TeamMember,
            phone: $request->filled('phone') ? $request->string('phone')->toString() : null,
            jobTitle: $request->filled('job_title') ? $request->string('job_title')->toString() : null,
            yearsOfExperience: $request->filled('years_of_experience') ? $request->integer('years_of_experience') : null,
        );
    }
}
