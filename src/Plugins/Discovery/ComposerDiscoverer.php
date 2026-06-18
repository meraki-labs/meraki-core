<?php

namespace Meraki\Core\Plugins\Discovery;

use Meraki\Core\Contracts\Plugin;

class ComposerDiscoverer implements PluginDiscoverer
{
    public function __construct(private string $installedJsonPath) {}

    public function discover(): array
    {
        if (!file_exists($this->installedJsonPath)) {
            return [];
        }

        $data = json_decode(file_get_contents($this->installedJsonPath), true);

        if (!is_array($data)) {
            return [];
        }

        // installed.json structure differs between Composer 1 and 2
        $packages = $data['packages'] ?? $data;

        if (!is_array($packages)) {
            return [];
        }

        $plugins = [];

        foreach ($packages as $package) {
            if (($package['type'] ?? '') !== 'meraki-plugin') {
                continue;
            }

            $fqcn = $package['extra']['meraki-plugin']['class'] ?? null;

            if (!$fqcn || !class_exists($fqcn)) {
                continue;
            }

            $plugin = new $fqcn();
            if ($plugin instanceof Plugin) {
                $plugins[] = $plugin;
            }
        }

        return $plugins;
    }
}
