<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LogoutAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class LogoutController extends Controller
{
    public function __invoke(#[CurrentUser] User $user, LogoutAction $action): JsonResponse
    {
        $action->handle($user);

        return ApiResponse::message(__('Logged out successfully.'));
    }
}
