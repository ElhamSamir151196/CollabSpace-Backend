<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Lets the frontend (and monitoring) check that the API is up.
     */
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'status' => 'ok',
            'version' => 'v1',
        ]);
    }
}
