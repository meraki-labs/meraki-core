<?php

namespace Meraki\Core\Support;

/**
 * @Author
 */
class ApiResponse
{
    /**
     * @param mixed|null $data
     * @param array $meta
     * @return array
     */
    public static function success(mixed $data = null, array $meta = []): array
    {
        return [
            'success' => true,
            'data'    => $data,
            'meta'    => $meta,
            'error'   => null,
        ];
    }

    /**
     * @param string $code
     * @param string $message
     * @param int $status
     * @return array
     */
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