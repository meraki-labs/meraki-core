<?php

namespace Meraki\Core\Exceptions;

class InvalidPluginArchiveException extends PluginInstallException
{
    public static function missingManifest(): self
    {
        return new self('Plugin archive is missing meraki.json manifest.');
    }

    public static function pathTraversal(string $entry): self
    {
        return new self("Unsafe entry in archive: [{$entry}].");
    }
}
