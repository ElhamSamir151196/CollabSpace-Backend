<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|--------------------------------------------------------------------------
|
| Only the React app may call the API from a browser.
| CORS_ALLOWED_ORIGINS: comma separated list (default: FRONTEND_URL).
| The API uses Bearer tokens, not cookies, so credentials are not needed.
|
*/

$origins = array_values(array_filter(array_map(
    fn (string $origin) => rtrim(trim($origin), '/'),
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:5173'))),
)));

// Locally, the API docs ("composer docs:serve") may call the API with "Try it out".
if (env('APP_ENV') === 'local') {
    $origins[] = 'http://localhost:8001';
}

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $origins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With', 'X-Socket-ID', 'X-Request-Id', 'Accept-Language'],

    // Readable by the React app: rate limit info, request id (for bug reports), file names.
    'exposed_headers' => ['X-Request-Id', 'X-RateLimit-Limit', 'X-RateLimit-Remaining', 'Retry-After', 'Content-Disposition'],

    // Browsers may cache the preflight answer for 2 hours.
    'max_age' => 7200,

    'supports_credentials' => false,

];
