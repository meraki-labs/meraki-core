<?php

namespace Meraki\Core\Plugin;

final class PluginMeta
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $version,
        public readonly string  $description = '',
        public readonly ?string $config      = null,
        public readonly array   $requires    = [],
    ) {}
}
