<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    public function success($data = [], string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], $status);
    }

    public function error(string $message = '', int $status = 400, $data = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data'    => $data,
            'message' => $message,
        ], $status);
    }
}
