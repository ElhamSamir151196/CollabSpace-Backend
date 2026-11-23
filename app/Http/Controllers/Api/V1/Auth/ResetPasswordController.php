<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\ResetPasswordAction;
use App\DTOs\Auth\ResetPasswordData;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\ResetPasswordRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ResetPasswordController extends Controller
{
    public function __invoke(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        $action->handle(ResetPasswordData::fromRequest($request));

        return ApiResponse::message(__('Your password has been reset. Please log in.'));
    }
}
