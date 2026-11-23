<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks deactivated users even if they still hold a valid token
 * (e.g. an admin deactivated them while they were logged in).
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            return ApiResponse::error(__('Your account has been deactivated.'), 403);
        }

        return $next($request);
    }
}
