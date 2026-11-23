<?php

namespace App\DTOs\Profile;

use App\Enums\AvailabilityStatus;
use App\Http\Requests\V1\Profile\UpdateProfileRequest;

/**
 * Only the fields the client actually sent (partial update).
 */
final readonly class UpdateProfileData
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    private function __construct(public array $attributes) {}

    public static function fromRequest(UpdateProfileRequest $request): self
    {
        $attributes = $request->safe()->only([
            'name',
            'email',
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
}
