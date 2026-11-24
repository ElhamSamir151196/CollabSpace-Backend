<?php

namespace App\Http\Resources\V1;

use App\Models\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * An exported PDF with a signed download link (valid 30 minutes, no token needed).
 *
 * @mixin ReportExport
 */
class ReportExportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->toArray(),
            'file_name' => $this->fileName(),
            'size' => $this->size,
            'note' => $this->note,
            'period' => [
                'from' => $this->period_from->toDateString(),
                'to' => $this->period_to->toDateString(),
            ],
            'project' => $this->whenLoaded('project', fn () => $this->project === null ? null : [
                'id' => $this->project->id,
                'slug' => $this->project->slug,
                'name' => $this->project->name,
            ]),
            'subject' => new UserSummaryResource($this->whenLoaded('subject')),
            'download_url' => URL::temporarySignedRoute(
                'v1.reports.exports.download',
                now()->addMinutes((int) config('collabspace.reports.link_ttl_minutes')),
                ['export' => $this->id],
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
