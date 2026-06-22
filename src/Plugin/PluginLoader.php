<?php

namespace Meraki\Core\Plugin;

use Illuminate\Contracts\Foundation\Application;
use Meraki\Core\Contracts\PluginInterface;
use Meraki\Core\Modules\PluginRegistry;

class PluginLoader
{
    /** @var array<string, class-string<PluginInterface>> */
    private array $discovered = [];

    /** @var array<string, true> guard against circular load */
    private array $loading = [];

    public function __construct(
        private readonly Application      $app,
        private readonly PluginDiscovery  $discovery,
        private readonly PluginRegistry   $registry,
        private readonly PluginStateStore $state,
    ) {}

    public function discover(): void
    {
        $this->discovered = $this->discovery->all();
    }

    /** @return array<string, class-string<PluginInterface>> */
    public function discovered(): array
    {
        return $this->discovered;
    }

    public function load(string $name): PluginInterface
    {
        if ($this->registry->has($name)) {
            return $this->registry->get($name);
        }

        if (isset($this->loading[$name])) {
            throw new \RuntimeException("Circular plugin dependency detected for [{$name}].");
        }

        $class = $this->discovered[$name]
            ?? throw new \RuntimeException("Plugin [{$name}] not discovered.");

        $this->loading[$name] = true;

        try {
            $plugin = $this->app->make($class);
            $plugin->register($this->app);
            $this->registry->register($plugin);
        } finally {
            unset($this->loading[$name]);
        }

        return $plugin;
    }

    public function loadAllEnabled(): void
    {
        foreach ($this->discovered as $name => $class) {
            if ($this->state->isEnabled($name)) {
                $this->load($name);
            }
        }
    }

    public function enable(string $name): void
    {
        $this->state->enable($name);
        $plugin = $this->load($name);
        $plugin->onEnable($this->app);
    }

    public function disable(string $name): void
    {
        if ($this->registry->has($name)) {
            $this->registry->get($name)->onDisable($this->app);
        }
        $this->state->disable($name);
    }

    public function isEnabled(string $name): bool
    {
        return $this->state->isEnabled($name);
    }

    public function install(string $name): void
    {
        $plugin = $this->load($name);
        $plugin->install($this->app);
        $this->state->markInstalled($name);
    }

    public function uninstall(string $name): void
    {
        $plugin = $this->load($name);
        $plugin->uninstall($this->app);
        $this->state->markUninstalled($name);
    }

    public function isInstalled(string $name): bool
    {
        return $this->state->isInstalled($name);
    }

    public function get(string $name): ?PluginInterface
    {
        return $this->registry->get($name);
    }

    /** @return PluginInterface[] */
    public function loaded(): array
    {
        return $this->registry->all();
    }
}
