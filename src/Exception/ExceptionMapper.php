<?php

namespace Meraki\Core\Exception;

class ExceptionMapper
{
    public static function toResponse(CoreException $e): array
    {
        return [
            'code' => $e->codeEnum->value,
            'message' => $e->getMessage(),
        ];
    }
}
