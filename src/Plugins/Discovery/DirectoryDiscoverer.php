<?php

namespace Meraki\Core\Plugins\Discovery;

use Meraki\Core\Contracts\Plugin;

class DirectoryDiscoverer implements PluginDiscoverer
{
    public function __construct(private string $path) {}

    public function discover(): array
    {
        if (!is_dir($this->path)) {
            return [];
        }

        $plugins = [];

        foreach (new \DirectoryIterator($this->path) as $entry) {
            if (!$entry->isDir() || $entry->isDot()) {
                continue;
            }

            $manifestFile = $entry->getPathname() . '/plugin.php';
            if (!file_exists($manifestFile)) {
                continue;
            }

            $fqcn = require $manifestFile;

            if (!is_string($fqcn) || !class_exists($fqcn)) {
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
