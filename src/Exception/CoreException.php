<?php

namespace Meraki\Core\Exception;

use Exception;

/**
 * @Author DatPA
 */
class CoreException extends Exception
{
    /**
     * @param ErrorCode $codeEnum
     * @param string $message
     * @param int $status
     */
    public function __construct(
        public readonly ErrorCode $codeEnum,
        string                    $message = '',
        int                       $status = 400
    )
    {
        parent::__construct($message, $status);
    }
}