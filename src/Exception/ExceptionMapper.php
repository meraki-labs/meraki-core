<?php

namespace Meraki\Core\Exception;

/**
 * @Author DatPA
 */
class ExceptionMapper
{
    /**
     * @param CoreException $e
     * @return array
     */
    public static function toResponse(CoreException $e): array
    {
        return [
            'code' => $e->codeEnum->value,
            'message' => $e->getMessage(),
        ];
    }
}
