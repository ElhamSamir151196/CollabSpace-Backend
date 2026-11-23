<?php

namespace App\DTOs\Team;

use App\Http\Requests\V1\Team\UpdateTeamRequest;

/**
 * Only the fields the client actually sent (partial update).
 */
final readonly class UpdateTeamData
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    private function __construct(public array $attributes) {}

    public static function fromRequest(UpdateTeamRequest $request): self
    {
        $attributes = $request->safe()->only(['name', 'description', 'color']);

        if (isset($attributes['color'])) {
            $attributes['color'] = strtoupper((string) $attributes['color']);
        }

        return new self($attributes);
    }
}
