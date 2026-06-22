<?php

namespace Meraki\Core\Plugin;

use Meraki\Core\Contracts\PluginInterface;

class PluginDiscovery
{
    /** @return array<string, class-string<PluginInterface>> */
    public function fromComposer(): array
    {
        if (!config('meraki.plugins.auto_discover', true)) {
            return [];
        }

        $installed = base_path('vendor/composer/installed.json');

        if (!file_exists($installed)) {
            return [];
        }

        $data = json_decode(file_get_contents($installed), true);

        if (!is_array($data)) {
            return [];
        }

        $packages = $data['packages'] ?? $data;

        if (!is_array($packages)) {
            return [];
        }

        $found = [];

        foreach ($packages as $package) {
            $class = $package['extra']['meraki']['plugin'] ?? null;

            if ($class !== null) {
                $found[$package['name']] = $class;
            }
        }

        return $found;
    }

    /** @return array<string, class-string<PluginInterface>> */
    public function fromConfig(): array
    {
        return config('meraki.plugins.list', []);
    }

    /** @return array<string, class-string<PluginInterface>> */
    public function all(): array
    {
        return array_merge($this->fromComposer(), $this->fromConfig());
    }
}
