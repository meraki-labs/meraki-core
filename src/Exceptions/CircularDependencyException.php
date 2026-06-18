<?php

namespace Meraki\Core\Exceptions;

class CircularDependencyException extends \RuntimeException
{
    public function __construct(array $cycle)
    {
        parent::__construct(
            'Circular dependency detected: ' . implode(' → ', $cycle) . ' → ' . $cycle[0]
        );
    }
}
