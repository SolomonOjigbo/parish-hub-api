<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ApiExceptionHandler extends ExceptionHandler
{
    /**
     * Report or log an exception.
     */
    public function report(Throwable $exception): void
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception): JsonResponse
    {
        // Always return JSON for API routes
        if ($request->is('api/*')) {
            $status = 500;
            $message = 'Internal Server Error';

            if (method_exists($exception, 'getStatusCode')) {
                $status = $exception->getStatusCode();
            }

            if (!empty($exception->getMessage())) {
                $message = $exception->getMessage();
            }

            return response()->json([
                'success' => false,
                'error'   => $message
            ], $status);
        }

        // For web routes, use default Laravel handler
        return parent::render($request, $exception);
    }
}
