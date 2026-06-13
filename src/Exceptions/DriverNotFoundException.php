<?php

namespace Meraki\Core\Exceptions;

class DriverNotFoundException extends MerakiException
{
    public static function for(string $capability, string $driver, array $available): self
    {
        $list = implode(', ', $available) ?: '(none registered)';
        return new self("Driver [{$driver}] for capability [{$capability}] not found. Available: [{$list}].");
    }
}
