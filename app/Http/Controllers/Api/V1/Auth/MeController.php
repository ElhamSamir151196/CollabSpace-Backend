<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\DTOs\Auth\AuthResult;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AuthSessionResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class MeController extends Controller
{
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return ApiResponse::success(new AuthSessionResource(new AuthResult($user)));
    }
}
