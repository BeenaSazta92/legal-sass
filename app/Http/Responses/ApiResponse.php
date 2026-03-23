<?php

namespace App\Http\Responses;

class ApiResponse
{
    /**
     * Success response
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Error response
     */
    public static function error($message = 'Error', $errors = null, $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError($errors, $message = 'Validation failed')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized($message = 'You are not authorized to perform this action')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }

    /**
     * Forbidden response
     */
    public static function forbidden($message = 'Forbidden')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    /**
     * Not found response
     */
    public static function notFound($message = 'Not found')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

}
