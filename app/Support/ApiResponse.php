<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;

/**
 * Builds every API response in one consistent shape:
 *
 * {
 *   "success": bool,
 *   "message": string|null,
 *   "data":    mixed,
 *   "meta":    object   (only when there is pagination or extra meta)
 *   "errors":  object   (only on failure)
 * }
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        if ($data instanceof ResourceCollection && self::isPaginated($data)) {
            return self::paginated($data, $message, $status, $meta, $headers);
        }

        $payload = [
            'success' => true,
            'message' => $message,
            'data' => self::resolve($data),
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status, $headers);
    }

    public static function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * For delete / action endpoints that have nothing to return.
     */
    public static function message(string $message, int $status = 200): JsonResponse
    {
        return self::success(null, $message, $status);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    public static function paginated(
        ResourceCollection $collection,
        ?string $message = null,
        int $status = 200,
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        /** @var array{data: mixed, meta?: array<string, mixed>, links?: array<string, mixed>} $resolved */
        $resolved = $collection->response()->getData(true);
        $pagination = $resolved['meta'] ?? [];

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resolved['data'],
            'meta' => array_merge($meta, [
                'pagination' => [
                    'current_page' => $pagination['current_page'] ?? null,
                    'per_page' => $pagination['per_page'] ?? null,
                    'total' => $pagination['total'] ?? null,
                    'last_page' => $pagination['last_page'] ?? null,
                    'next_cursor' => $pagination['next_cursor'] ?? null,
                    'prev_cursor' => $pagination['prev_cursor'] ?? null,
                ],
            ]),
        ], $status, $headers);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, string>  $headers
     */
    public static function error(
        string $message,
        int $status = 400,
        ?array $errors = null,
        array $headers = [],
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
            'data' => null,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status, $headers);
    }

    private static function resolve(mixed $data): mixed
    {
        if ($data instanceof JsonResource) {
            return $data->resolve(request());
        }

        return $data;
    }

    private static function isPaginated(ResourceCollection $collection): bool
    {
        return $collection->resource instanceof AbstractPaginator
            || $collection->resource instanceof AbstractCursorPaginator;
    }
}
