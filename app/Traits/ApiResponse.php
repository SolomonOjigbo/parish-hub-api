<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    protected function paginated(mixed $data, mixed $meta, string $message = ''): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'meta'    => $meta,
        ]);
    }

    protected function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        $response = ['success' => false, 'message' => $message];
        if ($errors) {
            $response['errors'] = $errors;
        }
        return response()->json($response, $status);
    }
}
