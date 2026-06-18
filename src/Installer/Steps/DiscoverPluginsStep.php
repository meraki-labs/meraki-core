<?php

namespace Meraki\Core\Installer\Steps;

use Meraki\Core\Installer\InstallerContext;
use Meraki\Core\Modules\PluginDiscovery;

class DiscoverPluginsStep implements Step
{
    public function run(InstallerContext $context): void
    {
        $discovery = app(PluginDiscovery::class);
        $discovery->clearCache();
        $manifests = $discovery->discover();

        $context->set('discovered_plugins', array_map(
            fn($m) => $m->id,
            $manifests
        ));
    }
}
