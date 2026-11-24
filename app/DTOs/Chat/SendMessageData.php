<?php

namespace App\DTOs\Chat;

use App\Http\Requests\V1\Chat\SendMessageRequest;
use Illuminate\Http\UploadedFile;

final readonly class SendMessageData
{
    /**
     * @param  list<int>  $mentionIds
     * @param  list<UploadedFile>  $files
     */
    public function __construct(
        public ?string $body,
        public ?int $parentId = null,
        public array $mentionIds = [],
        public array $files = [],
    ) {}

    public static function fromRequest(SendMessageRequest $request): self
    {
        $body = $request->filled('body') ? trim($request->string('body')->toString()) : null;

        return new self(
            body: $body !== '' ? $body : null,
            parentId: $request->filled('parent_id') ? $request->integer('parent_id') : null,
            mentionIds: $request->mentionIds(),
            files: $request->uploadedFiles(),
        );
    }
}
