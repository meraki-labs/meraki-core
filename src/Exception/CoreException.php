<?php

namespace Meraki\Core\Exception;

use Exception;

class CoreException extends Exception
{
    public function __construct(
        public readonly ErrorCode $codeEnum,
        string $message = '',
        int $status = 400
    ) {
        parent::__construct($message, $status);
    }
}