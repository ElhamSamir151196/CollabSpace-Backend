<?php

namespace App\Http\Resources\V1;

use App\Models\Attachment;
use App\Models\Task;
use App\Services\Files\AttachmentLinks;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A file row: name, type, size, who uploaded it, where it belongs,
 * and signed links (download + preview for images / PDF) that expire.
 *
 * @mixin Attachment
 */
class AttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $links = app(AttachmentLinks::class);
        $user = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->original_name,
            'extension' => $this->extension,
            'type' => $this->type()->toArray(),
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'size_human' => $this->humanSize($this->size),
            'source' => [
                'type' => $this->source(),
                'id' => $this->attachable_id,
                'title' => $this->whenLoaded('attachable', fn () => $this->attachable instanceof Task
                    ? $this->attachable->title
                    : $this->project?->name),
            ],
            'project' => $this->whenLoaded('project', fn () => $this->project === null ? null : [
                'id' => $this->project->id,
                'slug' => $this->project->slug,
                'name' => $this->project->name,
            ]),
            'uploaded_by' => new UserSummaryResource($this->whenLoaded('uploader')),
            'download_url' => $links->download($this->resource),
            'preview_url' => $links->preview($this->resource),
            // Chat files are removed with their message, so no per-file permission there.
            'can' => $this->when(! $this->isChatFile() && $user !== null, fn () => [
                'delete' => (bool) $user?->can('delete', $this->resource),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * 2048 -> "2 KB", 5_400_000 -> "5.1 MB" (no intl extension needed).
     */
    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $bytes;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return rtrim(rtrim(number_format($size, 1, '.', ''), '0'), '.').' '.$units[$unit];
    }
}
