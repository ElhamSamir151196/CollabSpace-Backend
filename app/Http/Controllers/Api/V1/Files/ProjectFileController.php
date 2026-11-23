<?php

namespace App\Http\Controllers\Api\V1\Files;

use App\Actions\Files\UploadAttachmentsAction;
use App\Filters\AttachmentFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Files\IndexAttachmentsRequest;
use App\Http\Requests\V1\Files\UploadAttachmentsRequest;
use App\Http\Resources\V1\AttachmentResource;
use App\Interfaces\Repositories\AttachmentRepositoryInterface;
use App\Models\Project;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Project > Files tab: /projects/{project}/files.
 * Lists the project's own files and the files of all its tasks.
 */
class ProjectFileController extends Controller
{
    public function __construct(private readonly AttachmentRepositoryInterface $attachments) {}

    public function index(IndexAttachmentsRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $files = $this->attachments->paginateForProject(
            $project,
            new AttachmentFilter($request->filters()),
            $request->integer('per_page', 15),
        );

        return ApiResponse::success(AttachmentResource::collection($files));
    }

    public function store(
        UploadAttachmentsRequest $request,
        Project $project,
        #[CurrentUser] User $user,
        UploadAttachmentsAction $action,
    ): JsonResponse {
        $files = $action->handle($project, $request->uploadedFiles(), $user);

        return ApiResponse::created(
            AttachmentResource::collection($files->load(['uploader', 'project', 'attachable'])),
            __('Files uploaded successfully.'),
        );
    }
}
