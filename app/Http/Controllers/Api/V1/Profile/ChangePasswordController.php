<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Actions\Profile\ChangePasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Profile\ChangePasswordRequest;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class ChangePasswordController extends Controller
{
    public function __invoke(ChangePasswordRequest $request, #[CurrentUser] User $user, ChangePasswordAction $action): JsonResponse
    {
        $action->handle($user, $request->string('password')->toString());

        return ApiResponse::message(__('Password changed successfully. Other devices have been logged out.'));
    }
}
