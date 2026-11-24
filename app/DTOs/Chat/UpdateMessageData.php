<?php

namespace App\DTOs\Chat;

use App\Http\Requests\V1\Chat\UpdateMessageRequest;

final readonly class UpdateMessageData
{
    /**
     * @param  list<int>|null  $mentionIds  null = keep the current mentions
     */
    public function __construct(
        public string $body,
        public ?array $mentionIds = null,
    ) {}

    public static function fromRequest(UpdateMessageRequest $request): self
    {
        return new self(
            body: trim($request->string('body')->toString()),
            mentionIds: $request->has('mention_ids') ? $request->mentionIds() : null,
        );
    }
}
