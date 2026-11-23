<?php

namespace App\Actions\Files;

use App\Interfaces\Repositories\AttachmentRepositoryInterface;
use App\Models\Attachment;
use App\Models\User;
use App\Services\Files\AttachmentStorage;
use Illuminate\Support\Facades\DB;

/**
 * Deletes the row and the file on disk (after the row is gone).
 * The activity log keeps a record of who deleted what.
 */
class DeleteAttachmentAction
{
    public function __construct(
        private readonly AttachmentRepositoryInterface $attachments,
        private readonly AttachmentStorage $storage,
    ) {}

    public function handle(Attachment $attachment, User $actor): void
    {
        DB::transaction(fn () => $this->attachments->delete($attachment));

        $this->storage->delete($attachment->disk, $attachment->path);

        activity('files')->causedBy($actor)->event('deleted')
            ->withProperties([
                'file' => $attachment->original_name,
                'project_id' => $attachment->project_id,
                'attachable_type' => $attachment->attachable_type,
                'attachable_id' => $attachment->attachable_id,
            ])
            ->log('File deleted');
    }
}
