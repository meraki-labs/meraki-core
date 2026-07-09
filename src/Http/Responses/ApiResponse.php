<?php

declare(strict_types=1);

namespace Meraki\Core\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Standardized API response format across all Meraki plugins.
 *
 * All successful responses:  { success: true,  data: ..., message: ... }
 * All error responses:       { success: false, message: ..., errors: ... }
 */
final class ApiResponse
{
    public static function success(
        mixed  $data    = null,
        string $message = 'OK',
        int    $status  = 200,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function created(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return static::success($data, $message, 201);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    public static function error(
        string $message,
        int    $status = 400,
        mixed  $errors = null,
    ): JsonResponse {
        $body = ['success' => false, 'message' => $message];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }

    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return static::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthenticated'): JsonResponse
    {
        return static::error($message, 401);
    }

    public static function forbidden(string $message = 'This action is unauthorized'): JsonResponse
    {
        return static::error($message, 403);
    }

    public static function unprocessable(
        array  $errors,
        string $message = 'The given data was invalid',
    ): JsonResponse {
        return static::error($message, 422, $errors);
    }

    public static function conflict(string $message = 'Conflict with current resource state'): JsonResponse
    {
        return static::error($message, 409);
    }

    public static function serverError(string $message = 'Server error'): JsonResponse
    {
        return static::error($message, 500);
    }
}
