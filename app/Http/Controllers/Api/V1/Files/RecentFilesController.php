<?php

namespace App\Http\Controllers\Api\V1\Files;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AttachmentResource;
use App\Interfaces\Repositories\AttachmentRepositoryInterface;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dashboard "Recent Files": latest files from the projects the user can see.
 * GET /files/recent?limit=5 (max 20).
 */
class RecentFilesController extends Controller
{
    public function __invoke(Request $request, #[CurrentUser] User $user, AttachmentRepositoryInterface $attachments): JsonResponse
    {
        $limit = max(1, min($request->integer('limit', 5), 20));

        return ApiResponse::success(AttachmentResource::collection($attachments->recentVisibleTo($user, $limit)));
    }
}
