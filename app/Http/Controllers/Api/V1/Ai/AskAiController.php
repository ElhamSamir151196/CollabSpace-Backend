<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Actions\Ai\AskAiAction;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * POST /ai/ask { question, project_id? } — the Dashboard "Ask AI" widget.
 */
class AskAiController extends Controller
{
    public function __invoke(Request $request, #[CurrentUser] User $user, AskAiAction $action): JsonResponse
    {
        $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'project_id' => ['nullable', 'integer'],
        ]);

        $project = null;

        if ($request->filled('project_id')) {
            $project = Project::query()->findOrFail($request->integer('project_id'));
            Gate::authorize('view', $project);
        }

        $response = $action->handle($user, trim($request->string('question')->toString()), $project);

        return ApiResponse::success(['answer' => $response->content, 'model' => $response->model]);
    }
}
