<?php

namespace Meraki\Core\Plugins\Discovery;

use Meraki\Core\Contracts\Plugin;

interface PluginDiscoverer
{
    /** @return Plugin[] */
    public function discover(): array;
}
