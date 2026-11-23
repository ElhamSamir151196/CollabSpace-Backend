<?php

namespace App\DTOs\Team;

final readonly class TeamMemberData
{
    public function __construct(
        public int $userId,
        public ?string $position = null,
        public bool $isLeader = false,
    ) {}

    /**
     * @param  array{user_id: int|string, position?: string|null, is_leader?: bool|int|string|null}  $row
     */
    public static function fromArray(array $row): self
    {
        $position = isset($row['position']) ? trim((string) $row['position']) : null;

        return new self(
            userId: (int) $row['user_id'],
            position: $position !== '' ? $position : null,
            isLeader: filter_var($row['is_leader'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );
    }

    /**
     * @param  list<array{user_id: int|string, position?: string|null, is_leader?: bool|int|string|null}>  $rows
     * @return list<self>
     */
    public static function collect(array $rows): array
    {
        return array_map(self::fromArray(...), $rows);
    }
}
