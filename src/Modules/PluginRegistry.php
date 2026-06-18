<?php

namespace Meraki\Core\Modules;

use Meraki\Core\Contracts\PluginInterface;

class PluginRegistry
{
    /** @var PluginInterface[] */
    protected array $plugins = [];

    public function register(PluginInterface $plugin): void
    {
        $this->plugins[$plugin->getMeta()->name] = $plugin;
    }

    /** @return PluginInterface[] */
    public function all(): array
    {
        return $this->plugins;
    }

    public function has(string $name): bool
    {
        return isset($this->plugins[$name]);
    }

    public function get(string $name): ?PluginInterface
    {
        return $this->plugins[$name] ?? null;
    }
}
