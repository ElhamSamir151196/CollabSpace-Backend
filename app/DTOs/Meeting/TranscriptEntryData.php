<?php

namespace App\DTOs\Meeting;

/**
 * One transcript line: who spoke, when (offset from the start), and what.
 */
final readonly class TranscriptEntryData
{
    public function __construct(
        public ?int $speakerId,
        public ?string $speakerName,
        public int $offsetSeconds,
        public string $content,
        public string $language = 'en',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        $name = isset($row['speaker_name']) ? trim((string) $row['speaker_name']) : null;

        return new self(
            speakerId: isset($row['speaker_id']) ? (int) $row['speaker_id'] : null,
            speakerName: $name !== '' ? $name : null,
            offsetSeconds: self::parseOffset($row['offset'] ?? 0),
            content: trim((string) ($row['content'] ?? '')),
            language: isset($row['language']) ? strtolower((string) $row['language']) : 'en',
        );
    }

    /**
     * 40 / "40" -> 40, "00:40" -> 40, "1:02:05" -> 3725.
     */
    public static function parseOffset(mixed $value): int
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return (int) $value;
        }

        $parts = array_map('intval', explode(':', (string) $value));

        return array_reduce($parts, fn (int $total, int $part) => $total * 60 + $part, 0);
    }
}
