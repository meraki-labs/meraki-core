<?php

namespace Meraki\Core\Events;

use Meraki\Core\Contracts\Plugin;

class PluginEnabled
{
    public function __construct(
        public readonly string $id,
        public readonly Plugin $plugin,
    ) {}
}
