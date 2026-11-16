<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        /**
         * ==============================
         * HANDLE MODEL NOT FOUND (UUID)
         * ==============================
         */
        $this->renderable(function (Throwable $e, Request $request) {

            if (!$request->expectsJson()) {
                return null;
            }

            /**
             * Case 1: Eloquent findOrFail()
             */
            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            /**
             * Case 2: Route not found OR converted model exception
             */
            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            /**
             * Case 3: Everything else
             */
            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Server Error',
            ], 500);
        });
    }
}