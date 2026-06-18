<?php

namespace Meraki\Core\Exceptions;

class MissingDependencyException extends \RuntimeException
{
    public function __construct(
        public readonly string $missing,
        public readonly string $requiredBy
    ) {
        parent::__construct(
            "Package \"{$requiredBy}\" requires \"{$missing}\" which is not registered."
        );
    }
}
