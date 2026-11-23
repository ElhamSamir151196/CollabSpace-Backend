<?php

namespace App\Actions\Files;

use App\Interfaces\Repositories\AttachmentRepositoryInterface;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Files\AttachmentStorage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Saves files to a project or a task.
 * If anything fails, the files already written to disk are removed again,
 * so the disk never keeps files that have no database row.
 */
class UploadAttachmentsAction
{
    public function __construct(
        private readonly AttachmentRepositoryInterface $attachments,
        private readonly AttachmentStorage $storage,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return Collection<int, Attachment>
     */
    public function handle(Project|Task $target, array $files, User $uploader): Collection
    {
        $projectId = $target instanceof Project ? $target->id : $target->project_id;
        $directory = "projects/{$projectId}";
        $stored = [];

        try {
            foreach ($files as $file) {
                $stored[] = $this->storage->store($file, $directory);
            }

            $attachments = DB::transaction(fn () => new Collection(array_map(
                fn (array $file) => $this->attachments->create([
                    ...$file,
                    'attachable_type' => $target::class,
                    'attachable_id' => $target->id,
                    'project_id' => $projectId,
                    'uploaded_by' => $uploader->id,
                ]),
                $stored,
            )));
        } catch (Throwable $e) {
            foreach ($stored as $file) {
                $this->storage->delete($file['disk'], $file['path']);
            }

            throw $e;
        }

        activity('files')->causedBy($uploader)->performedOn($target)->event('uploaded')
            ->withProperties(['files' => $attachments->pluck('original_name')->all()])
            ->log('Files uploaded');

        return $attachments;
    }
}
