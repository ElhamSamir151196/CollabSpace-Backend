<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Updates users.last_seen_at ("last seen 5 minutes ago") after authenticated
 * API calls. Written at most once a minute per user to keep it cheap.
 * Live "online now" comes from the "online" presence channel instead.
 */
class TrackLastSeen
{
    private const EVERY_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if ($user instanceof User
            && ($user->last_seen_at === null || $user->last_seen_at->diffInSeconds(now()) >= self::EVERY_SECONDS)) {
            // Plain query: no "updated_at" change, no activity-log entry.
            User::query()->whereKey($user->id)->toBase()->update(['last_seen_at' => now()]);
        }

        return $response;
    }
}
