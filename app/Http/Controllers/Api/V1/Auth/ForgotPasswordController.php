<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\SendPasswordResetLinkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\ForgotPasswordRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController extends Controller
{
    public function __invoke(ForgotPasswordRequest $request, SendPasswordResetLinkAction $action): JsonResponse
    {
        $action->handle($request->string('email')->toString());

        return ApiResponse::message(__('If this email is registered, a reset link has been sent.'));
    }
}
