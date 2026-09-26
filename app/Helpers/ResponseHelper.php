<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ResponseHelper
{
    public static function success(mixed $data = [], ?string $message = null, int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? __('Response successful'),
            'data' => $data,
        ], $statusCode);
    }

    public static function error(mixed $errors = null, ?string $message = null, int $statusCode = 400): JsonResponse
    {
        $resolvedMessage = $message;

        if (!empty($errors)) {
            if ($errors instanceof \Illuminate\Contracts\Support\MessageProvider) {
                $errorMessages = $errors->getMessageBag()->all();
            } elseif ($errors instanceof \Illuminate\Contracts\Support\Arrayable) {
                $errorMessages = \Illuminate\Support\Arr::flatten($errors->toArray());
            } elseif (is_array($errors)) {
                $errorMessages = \Illuminate\Support\Arr::flatten($errors);
            } else {
                $errorMessages = [(string) $errors];
            }

            $errorMessages = array_filter(array_map('trim', $errorMessages));
            if (!empty($errorMessages)) {
                $resolvedMessage = implode(' ', $errorMessages);
            }
        }

        return response()->json([
            'success' => false,
            'message' => $resolvedMessage ?? __('An error occurred'),
        ], $statusCode);
    }
}
