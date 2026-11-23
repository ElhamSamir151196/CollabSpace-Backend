<?php

namespace App\DTOs\Team;

use App\Http\Requests\V1\Team\UpdateTeamMemberRequest;

final readonly class UpdateTeamMemberData
{
    /**
     * @param  array{position?: string|null, is_leader?: bool}  $attributes
     */
    private function __construct(public array $attributes) {}

    public static function fromRequest(UpdateTeamMemberRequest $request): self
    {
        $attributes = [];

        if ($request->has('position')) {
            $position = trim((string) $request->validated('position'));
            $attributes['position'] = $position !== '' ? $position : null;
        }

        if ($request->has('is_leader')) {
            $attributes['is_leader'] = $request->boolean('is_leader');
        }

        return new self($attributes);
    }

    public function makesLeader(): bool
    {
        return ($this->attributes['is_leader'] ?? false) === true;
    }
}
