<?php

namespace Meraki\Core\Exceptions;

use RuntimeException;

class DuplicatePluginIdException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Duplicate plugin ID detected: {$id}");
    }
}
