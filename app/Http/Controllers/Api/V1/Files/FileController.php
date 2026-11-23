<?php

namespace App\Http\Controllers\Api\V1\Files;

use App\Actions\Files\DeleteAttachmentAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AttachmentResource;
use App\Models\Attachment;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * A single file: /files/{file}.
 * "show" is also how the frontend gets fresh links when the old ones expired.
 */
class FileController extends Controller
{
    public function show(Attachment $file): JsonResponse
    {
        Gate::authorize('view', $file);

        return ApiResponse::success(new AttachmentResource($file->load(['uploader', 'project', 'attachable'])));
    }

    public function destroy(Attachment $file, #[CurrentUser] User $user, DeleteAttachmentAction $action): JsonResponse
    {
        Gate::authorize('delete', $file);

        $action->handle($file, $user);

        return ApiResponse::message(__('File deleted successfully.'));
    }
}
