<?php

namespace App\DTOs\Search;

/**
 * One line of the global search dropdown. "meta" holds what the frontend
 * needs to open it (project slug, conversation id ...).
 */
final readonly class SearchResult
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $type,
        public int $id,
        public string $title,
        public ?string $subtitle = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'meta' => (object) $this->meta,
        ];
    }
}
