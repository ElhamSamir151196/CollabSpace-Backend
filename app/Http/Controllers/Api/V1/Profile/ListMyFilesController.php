<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Filters\AttachmentFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Files\IndexAttachmentsRequest;
use App\Http\Resources\V1\AttachmentResource;
use App\Interfaces\Repositories\AttachmentRepositoryInterface;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * Profile > Files: files the signed-in user uploaded.
 */
class ListMyFilesController extends Controller
{
    public function __invoke(
        IndexAttachmentsRequest $request,
        #[CurrentUser] User $user,
        AttachmentRepositoryInterface $attachments,
    ): JsonResponse {
        $files = $attachments->paginateUploadedBy(
            $user,
            new AttachmentFilter($request->filters()),
            $request->integer('per_page', 15),
        );

        return ApiResponse::success(AttachmentResource::collection($files));
    }
}
