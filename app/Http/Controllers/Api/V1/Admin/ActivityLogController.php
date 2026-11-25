<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\Permission;
use App\Filters\ActivityFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\IndexActivityLogRequest;
use App\Http\Resources\V1\ActivityResource;
use App\Models\Activity;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Admin > Activity log (read only): who did what and when
 * (logins, role changes, project / task changes, uploads, AI usage ...).
 */
class ActivityLogController extends Controller
{
    public function index(IndexActivityLogRequest $request): JsonResponse
    {
        $activities = Activity::query()
            ->filter(new ActivityFilter($request->validated()))
            ->with('causer')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::success(ActivityResource::collection($activities));
    }

    public function show(Activity $activity): JsonResponse
    {
        Gate::authorize(Permission::ActivityLogView->value);

        return ApiResponse::success(new ActivityResource($activity->load('causer')));
    }
}
