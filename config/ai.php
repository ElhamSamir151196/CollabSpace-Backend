<?php

use App\Services\Ai\Providers\FakeAiProvider;
use App\Services\Ai\Providers\GeminiProvider;

/*
|--------------------------------------------------------------------------
| AI
|--------------------------------------------------------------------------
|
| The app talks to AI through App\Interfaces\Ai\AiProvider. Each provider
| below is one class; AiManager picks the one to use (the admin can change
| it from the app, see ai_settings). Adding a provider = one new class +
| one entry here.
|
| "fake" answers instantly without any API key: use it for development and
| tests until the real key is in .env.
|
*/

return [

    // Used when the admin has not chosen one in the app.
    'default' => env('AI_PROVIDER', 'fake'),

    'providers' => [

        'gemini' => [
            'driver' => GeminiProvider::class,
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'timeout' => (int) env('GEMINI_TIMEOUT', 60),
        ],

        'fake' => [
            'driver' => FakeAiProvider::class,
            'model' => 'fake-model',
        ],

    ],

    // How much data / history is sent with each request (keeps cost and latency down).
    'context' => [
        'max_projects' => 10,
        'max_tasks' => 30,
        'max_meetings' => 10,
        'max_files' => 15,
        'history_messages' => 20,
    ],

    // Project insights are cached so opening the page does not call the AI every time.
    'insights_cache_minutes' => 60,

];
