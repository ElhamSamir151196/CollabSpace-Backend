<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LoginWithGoogleAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\RegisterUserAction;
use App\DTOs\Auth\AuthResult;
use App\DTOs\Auth\GoogleLoginData;
use App\DTOs\Auth\LoginData;
use App\DTOs\Auth\RegisterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\GoogleLoginRequest;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Requests\V1\Auth\RegisterRequest;
use App\Http\Resources\V1\AuthSessionResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $result = $action->handle(RegisterData::fromRequest($request));

        return ApiResponse::created(new AuthSessionResource($result), __('Account created successfully.'));
    }

    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $result = $action->handle(LoginData::fromRequest($request));

        return ApiResponse::success(new AuthSessionResource($result), __('Logged in successfully.'));
    }

    public function google(GoogleLoginRequest $request, LoginWithGoogleAction $action): JsonResponse
    {
        $result = $action->handle(GoogleLoginData::fromRequest($request));

        return ApiResponse::success(new AuthSessionResource($result), __('Logged in successfully.'));
    }

    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->handle($user);

        return ApiResponse::message(__('Logged out successfully.'));
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success(new AuthSessionResource(new AuthResult($user)));
    }
}
