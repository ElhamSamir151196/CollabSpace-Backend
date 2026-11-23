<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Profile\ChangePasswordAction;
use App\Actions\Profile\UpdateAvatarAction;
use App\Actions\Profile\UpdateProfileAction;
use App\DTOs\Profile\UpdateProfileData;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Profile\ChangePasswordRequest;
use App\Http\Requests\V1\Profile\UpdateAvatarRequest;
use App\Http\Requests\V1\Profile\UpdateProfileRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The signed-in user's own profile (Profile > Overview / Settings).
 * Tasks, files and reports tabs are added with their modules.
 */
class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($this->currentUser($request)->load('roles')));
    }

    public function update(UpdateProfileRequest $request, UpdateProfileAction $action): JsonResponse
    {
        $user = $action->handle($this->currentUser($request), UpdateProfileData::fromRequest($request));

        return ApiResponse::success(new UserResource($user->load('roles')), __('Profile updated successfully.'));
    }

    public function updateAvatar(UpdateAvatarRequest $request, UpdateAvatarAction $action): JsonResponse
    {
        $user = $action->handle($this->currentUser($request), $request->file('avatar'));

        return ApiResponse::success(new UserResource($user->load('roles')), __('Profile picture updated.'));
    }

    public function destroyAvatar(Request $request, UpdateAvatarAction $action): JsonResponse
    {
        $user = $action->handle($this->currentUser($request), null);

        return ApiResponse::success(new UserResource($user->load('roles')), __('Profile picture removed.'));
    }

    public function updatePassword(ChangePasswordRequest $request, ChangePasswordAction $action): JsonResponse
    {
        $action->handle($this->currentUser($request), $request->string('password')->toString());

        return ApiResponse::message(__('Password changed successfully. Other devices have been logged out.'));
    }

    private function currentUser(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
