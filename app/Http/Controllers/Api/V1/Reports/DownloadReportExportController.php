<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Models\ReportExport;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * GET /reports/exports/{export}/download — protected by the "signed" middleware.
 * The link is only handed out to the person who generated the report.
 */
class DownloadReportExportController extends Controller
{
    public function __invoke(ReportExport $export): StreamedResponse
    {
        $disk = Storage::disk($export->disk);

        abort_unless($disk->exists($export->path), 404);

        return $disk->download($export->path, $export->load('project')->fileName(), [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }
}
