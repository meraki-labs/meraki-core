<?php

namespace Meraki\Core\Exceptions;

class PluginConflictException extends PluginInstallException
{
    public static function alreadyInstalled(string $id): self
    {
        return new self("Plugin [{$id}] is already installed at plugins/{$id}/.");
    }
}
