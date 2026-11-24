<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Actions\Reports\ExportReportAction;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Reports\ExportReportRequest;
use App\Http\Resources\V1\ReportExportResource;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\Reports\ReportScopeResolver;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * /reports/exports — "Submit a detailed report" (store) and its history.
 */
class ReportExportController extends Controller
{
    public function index(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $exports = ReportExport::query()
            ->where('user_id', $user->id)
            ->with(['project', 'subject'])
            ->latest()
            ->latest('id')
            ->paginate(max(1, min($request->integer('per_page', 15), 100)));

        return ApiResponse::success(ReportExportResource::collection($exports));
    }

    public function store(
        ExportReportRequest $request,
        #[CurrentUser] User $user,
        ReportScopeResolver $scopes,
        ExportReportAction $action,
    ): JsonResponse {
        $type = $request->type();
        $subject = $type === ReportType::User ? $request->subject() : null;

        // Your own report covers every project you can see; anything else
        // only the projects you may report on.
        $scope = $subject !== null && $subject->is($user)
            ? $scopes->forDashboard($user, $request->projectId(), $request->from(), $request->to())
            : $scopes->forReports($user, $request->projectId(), $request->from(), $request->to());

        $export = $action->handle($type, $scope, $user, $request->note(), $request->projectId(), $subject);

        return ApiResponse::created(
            new ReportExportResource($export->load(['project', 'subject'])),
            __('Report generated. Use download_url to download the PDF.'),
        );
    }

    public function destroy(ReportExport $export): JsonResponse
    {
        Gate::authorize('delete', $export);

        Storage::disk($export->disk)->delete($export->path);
        $export->delete();

        return ApiResponse::message(__('Report deleted.'));
    }
}
