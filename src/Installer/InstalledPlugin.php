<?php

namespace Meraki\Core\Installer;

final class InstalledPlugin
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $version,
        public readonly string $path,
    ) {}
}
