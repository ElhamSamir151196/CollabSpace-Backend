<?php

namespace App\DTOs\Team;

use App\Http\Requests\V1\Team\StoreTeamRequest;

final readonly class CreateTeamData
{
    /**
     * @param  list<TeamMemberData>  $members
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $color = null,
        public array $members = [],
    ) {}

    public static function fromRequest(StoreTeamRequest $request): self
    {
        /** @var list<array{user_id: int|string, position?: string|null, is_leader?: bool|null}> $members */
        $members = $request->validated('members', []);

        return new self(
            name: $request->string('name')->trim()->toString(),
            description: $request->filled('description') ? $request->string('description')->trim()->toString() : null,
            color: $request->filled('color') ? strtoupper($request->string('color')->toString()) : null,
            members: TeamMemberData::collect($members),
        );
    }
}
