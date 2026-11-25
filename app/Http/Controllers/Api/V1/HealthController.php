<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class HealthController extends Controller
{
    /**
     * Lets the frontend (and monitoring) check that the API, the database
     * and the cache are up. Returns 503 when one of them is down
     * (details go to the log, never to the response).
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check('database', fn () => DB::select('select 1')),
            'cache' => $this->check('cache', function () {
                Cache::put('health-check', true, 10);

                return Cache::get('health-check') === true ?: throw new RuntimeException('Cache read failed.');
            }),
        ];

        $healthy = ! in_array('down', $checks, true);
        $data = ['status' => 'ok', 'version' => 'v1', 'checks' => $checks];

        return $healthy
            ? ApiResponse::success($data)
            : ApiResponse::error(__('Some services are not available.'), 503, ['checks' => $checks]);
    }

    private function check(string $name, callable $probe): string
    {
        try {
            $probe();

            return 'up';
        } catch (Throwable $e) {
            Log::error("Health check failed: {$name}.", ['error' => $e->getMessage()]);

            return 'down';
        }
    }
}
