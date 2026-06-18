<?php

namespace Meraki\Core\Exceptions;

class CapabilityNotSupportedException extends MerakiException
{
    public static function for(string $capability): self
    {
        return new self("No default driver for capability [{$capability}].");
    }
}
