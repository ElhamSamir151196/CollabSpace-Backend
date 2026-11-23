<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Actions\Profile\UpdateAvatarAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class DeleteAvatarController extends Controller
{
    public function __invoke(#[CurrentUser] User $user, UpdateAvatarAction $action): JsonResponse
    {
        $user = $action->handle($user, null);

        return ApiResponse::success(new UserResource($user->load('roles')), __('Profile picture removed.'));
    }
}
