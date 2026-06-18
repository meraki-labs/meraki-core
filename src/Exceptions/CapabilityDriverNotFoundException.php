<?php

namespace Meraki\Core\Exceptions;

class CapabilityDriverNotFoundException extends MerakiException
{
    public static function for(string $driver, string $capability, array $available): self
    {
        $list = implode(', ', $available) ?: 'none';
        return new self(
            "Driver [{$driver}] for capability [{$capability}] not found. Available: [{$list}]."
        );
    }
}
