<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LoginWithGoogleAction;
use App\DTOs\Auth\GoogleLoginData;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\GoogleLoginRequest;
use App\Http\Resources\V1\AuthSessionResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class GoogleLoginController extends Controller
{
    public function __invoke(GoogleLoginRequest $request, LoginWithGoogleAction $action): JsonResponse
    {
        $result = $action->handle(GoogleLoginData::fromRequest($request));

        return ApiResponse::success(new AuthSessionResource($result), __('Logged in successfully.'));
    }
}
