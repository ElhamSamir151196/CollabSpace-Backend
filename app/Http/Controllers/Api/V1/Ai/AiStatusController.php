<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiSettings;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * GET /ai/status — lets the frontend show or hide the AI features.
 * No secrets here (keys never leave .env).
 */
class AiStatusController extends Controller
{
    public function __invoke(AiSettings $settings): JsonResponse
    {
        $all = $settings->all();

        return ApiResponse::success([
            'enabled' => $all['enabled'],
            'allowed_modules' => $all['allowed_modules'],
        ]);
    }
}
