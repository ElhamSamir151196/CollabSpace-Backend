<?php

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Actions\Admin\ChangeUserRoleAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\User\UpdateUserRoleRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class UpdateUserRoleController extends Controller
{
    public function __invoke(
        UpdateUserRoleRequest $request,
        User $user,
        #[CurrentUser] User $actor,
        ChangeUserRoleAction $action,
    ): JsonResponse {
        $role = $request->enum('role', UserRole::class) ?? UserRole::TeamMember;

        $user = $action->handle($actor, $user, $role);

        return ApiResponse::success(new UserResource($user), __('Role updated successfully.'));
    }
}
