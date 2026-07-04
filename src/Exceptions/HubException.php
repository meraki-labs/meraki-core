<?php

namespace Meraki\Core\Exceptions;

final class HubException extends MerakiException
{
    public static function pluginNotFound(string $hubId): self
    {
        return new self("Plugin [{$hubId}] not found on hub.");
    }

    public static function requestFailed(string $context, int $status, string $detail): self
    {
        return new self("Hub request failed [{$context}]: HTTP {$status} — {$detail}");
    }
}
