<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class HelloController extends Controller
{
    /**
     * @OA\Get(
     *     path="/hello",
     *     tags={"Test"},
     *     summary="Hello World API",
     *     description="Returns a Hello World message",
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Hello World 🚀")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Hello World 🚀'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/up",
     *     tags={"Health"},
     *     summary="Health check",
     *     description="Checks if the API is running",
     *     @OA\Response(
     *         response=200,
     *         description="API is healthy",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="ok")
     *         )
     *     )
     * )
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok'
        ]);
    }
}
