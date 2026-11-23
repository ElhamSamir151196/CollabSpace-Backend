<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\DTOs\Auth\RegisterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\RegisterRequest;
use App\Http\Resources\V1\AuthSessionResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $result = $action->handle(RegisterData::fromRequest($request));

        return ApiResponse::created(new AuthSessionResource($result), __('Account created successfully.'));
    }
}
