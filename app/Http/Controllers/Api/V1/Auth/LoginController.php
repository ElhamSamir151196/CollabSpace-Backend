<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LoginAction;
use App\DTOs\Auth\LoginData;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Resources\V1\AuthSessionResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $result = $action->handle(LoginData::fromRequest($request));

        return ApiResponse::success(new AuthSessionResource($result), __('Logged in successfully.'));
    }
}
