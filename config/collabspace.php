<?php

/*
|--------------------------------------------------------------------------
| CollabSpace application settings
|--------------------------------------------------------------------------
*/

return [

    // The React app. Used to build links sent by email (e.g. reset password).
    'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/'),

    'auth' => [
        // Lifetime of an API token for a normal login.
        'token_ttl_hours' => (int) env('AUTH_TOKEN_TTL_HOURS', 24),

        // Lifetime when the user ticks "Remember me".
        'remember_ttl_days' => (int) env('AUTH_REMEMBER_TTL_DAYS', 30),
    ],

    'avatars' => [
        'disk' => 'public',
        'directory' => 'avatars',
        'max_kb' => 2048,
    ],

    // Project / task files. Stored on a PRIVATE disk (storage/app/private),
    // downloaded only through signed links that expire.
    'files' => [
        'disk' => env('FILES_DISK', 'local'),
        'max_kb' => (int) env('FILES_MAX_KB', 20480), // 20 MB per file
        'max_per_upload' => 10,
        'link_ttl_minutes' => 30,
    ],

    // Exported PDF reports: private disk, downloaded through signed links.
    'reports' => [
        'disk' => env('REPORTS_DISK', 'local'),
        'directory' => 'reports',
        'link_ttl_minutes' => 30,
    ],

    // Meeting cover images are public (like avatars), under random names.
    'meetings' => [
        'cover_disk' => 'public',
        'cover_directory' => 'meeting-covers',
        'cover_max_kb' => 4096,
    ],

    // First admin account created by AdminUserSeeder.
    'admin' => [
        'name' => env('ADMIN_NAME', 'System Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
