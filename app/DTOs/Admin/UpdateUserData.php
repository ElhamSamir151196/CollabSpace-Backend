<?php

namespace App\DTOs\Admin;

use App\Enums\AvailabilityStatus;
use App\Http\Requests\V1\Admin\User\UpdateUserRequest;

/**
 * Only the fields the admin actually sent (partial update).
 */
final readonly class UpdateUserData
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    private function __construct(public array $attributes) {}

    public static function fromRequest(UpdateUserRequest $request): self
    {
        $attributes = $request->safe()->only([
            'name',
            'email',
            'password',
            'phone',
            'job_title',
            'years_of_experience',
            'availability_status',
        ]);

        if (isset($attributes['availability_status'])) {
            $attributes['availability_status'] = AvailabilityStatus::from($attributes['availability_status']);
        }

        return new self($attributes);
    }

    public function changesPassword(): bool
    {
        return array_key_exists('password', $this->attributes);
    }
}
