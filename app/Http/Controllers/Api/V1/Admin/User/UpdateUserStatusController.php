<?php

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Actions\Admin\ChangeUserStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\UpdateUserStatusRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class UpdateUserStatusController extends Controller
{
    public function __invoke(
        UpdateUserStatusRequest $request,
        User $user,
        #[CurrentUser] User $actor,
        ChangeUserStatusAction $action,
    ): JsonResponse {
        $user = $action->handle($actor, $user, $request->boolean('is_active'));

        $message = $user->is_active ? __('User activated.') : __('User deactivated.');

        return ApiResponse::success(new UserResource($user->load('roles')), $message);
    }
}
