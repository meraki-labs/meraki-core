<?php

namespace Meraki\Core\Events;

use Meraki\Core\Plugins\PluginManager;

class PluginsBooted
{
    public function __construct(
        public readonly PluginManager $manager,
    ) {}
}
