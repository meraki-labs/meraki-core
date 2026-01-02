<?php

namespace Meraki\Core\Http;

class ApiResponse
{
    public static function success(mixed $data = null, array $meta = []): array
    {
        return [
            'success' => true,
            'data'    => $data,
            'meta'    => $meta,
            'error'   => null,
        ];
    }

    public static function error(string $code, string $message, int $status = 400): array
    {
        return [
            'success' => false,
            'data'    => null,
            'error'   => [
                'code'    => $code,
                'message' => $message,
            ],
        ];
    }
}