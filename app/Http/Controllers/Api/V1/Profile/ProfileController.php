<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Actions\Profile\UpdateProfileAction;
use App\DTOs\Profile\UpdateProfileData;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Profile\UpdateProfileRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * The signed-in user's own profile (Profile > Overview / Settings).
 * Avatar and password have their own invokable controllers.
 */
class ProfileController extends Controller
{
    public function show(#[CurrentUser] User $user): JsonResponse
    {
        return ApiResponse::success(new UserResource($user->load('roles')));
    }

    public function update(UpdateProfileRequest $request, #[CurrentUser] User $user, UpdateProfileAction $action): JsonResponse
    {
        $user = $action->handle($user, UpdateProfileData::fromRequest($request));

        return ApiResponse::success(new UserResource($user->load('roles')), __('Profile updated successfully.'));
    }
}
