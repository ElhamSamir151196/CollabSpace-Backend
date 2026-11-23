<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Turns every exception thrown inside /api/* into the ApiResponse shape.
 *
 * Note: by the time this runs Laravel has already converted
 *  - ModelNotFoundException  -> NotFoundHttpException (404)
 *  - AuthorizationException  -> AccessDeniedHttpException (403)
 */
class ApiExceptionRenderer
{
    public function __invoke(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        return match (true) {
            $e instanceof BusinessException => ApiResponse::error(
                $e->getMessage(),
                $e->status(),
            ),

            $e instanceof ValidationException => ApiResponse::error(
                __('The given data was invalid.'),
                422,
                $e->errors(),
            ),

            $e instanceof AuthenticationException => ApiResponse::error(
                __('Unauthenticated.'),
                401,
            ),

            $e instanceof HttpExceptionInterface => ApiResponse::error(
                $this->httpMessage($e),
                $e->getStatusCode(),
                null,
                $e->getHeaders(),
            ),

            default => $this->serverError($e),
        };
    }

    private function httpMessage(HttpExceptionInterface $e): string
    {
        return match ($e->getStatusCode()) {
            // Never leak model class names / ids ("No query results for model [App\Models\X] 5").
            404 => __('Resource not found.'),
            403 => __('This action is unauthorized.'),
            405 => __('Method not allowed.'),
            429 => __('Too many requests. Please try again later.'),
            default => $e->getMessage() !== ''
                ? $e->getMessage()
                : __('Request failed.'),
        };
    }

    private function serverError(Throwable $e): JsonResponse
    {
        $errors = null;

        if (config('app.debug')) {
            $errors = [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }

        return ApiResponse::error(__('Server error.'), 500, $errors);
    }
}
