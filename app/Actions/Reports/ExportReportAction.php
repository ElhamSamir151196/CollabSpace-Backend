<?php

namespace App\Actions\Reports;

use App\DTOs\Reports\ReportScope;
use App\Enums\ReportType;
use App\Interfaces\Reports\PdfRenderer;
use App\Models\Project;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\Reports\ReportService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Builds the report numbers, renders them into a PDF, stores the file on the
 * private disk and records it in report_exports (history).
 */
class ExportReportAction
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly PdfRenderer $pdf,
    ) {}

    public function handle(
        ReportType $type,
        ReportScope $scope,
        User $requestedBy,
        ?string $note = null,
        ?int $projectId = null,
        ?User $subject = null,
    ): ReportExport {
        $data = match ($type) {
            ReportType::Overview => $this->reports->overview($scope),
            ReportType::Project => $this->reports->projects($scope),
            ReportType::Task => $this->reports->tasks($scope),
            ReportType::Team => $this->reports->teams($scope),
            ReportType::User => $this->reports->user($subject ?? $requestedBy, $scope),
        };

        $title = $type->label();

        $html = view("reports.pdf.{$type->value}", [
            'title' => $title,
            'data' => $data,
            'note' => $note,
            'period' => $scope->from->toFormattedDateString().' – '.$scope->to->toFormattedDateString(),
            'projectName' => $projectId !== null ? Project::query()->whereKey($projectId)->value('name') : null,
            'generatedBy' => $requestedBy->name,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->render();

        $bytes = $this->pdf->render($html, $title);

        $disk = (string) config('collabspace.reports.disk');
        $path = config('collabspace.reports.directory')."/{$requestedBy->id}/".Str::uuid()->toString().'.pdf';

        Storage::disk($disk)->put($path, $bytes);

        try {
            return ReportExport::query()->create([
                'user_id' => $requestedBy->id,
                'type' => $type,
                'project_id' => $projectId,
                'subject_user_id' => $type === ReportType::User ? ($subject ?? $requestedBy)->id : null,
                'note' => $note,
                'period_from' => $scope->from->toDateString(),
                'period_to' => $scope->to->toDateString(),
                'disk' => $disk,
                'path' => $path,
                'size' => strlen($bytes),
            ]);
        } catch (Throwable $e) {
            Storage::disk($disk)->delete($path);

            throw $e;
        }
    }
}
