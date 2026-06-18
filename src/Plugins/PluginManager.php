<?php

namespace Meraki\Core\Plugins;

use Meraki\Core\Contracts\Plugin;
use Meraki\Core\Events\PluginEnabled;
use Meraki\Core\Events\PluginDisabled;
use Meraki\Core\Plugins\Discovery\PluginDiscoverer;
use RuntimeException;

class PluginManager
{
    /** @var array<string, Plugin> */
    private array $discovered = [];
    private bool $hasDiscovered = false;

    /** @param PluginDiscoverer[] $discoverers */
    public function __construct(
        private array $discoverers,
        private PluginRepository $repo,
    ) {}

    /** @return Plugin[] */
    public function discover(): array
    {
        $this->discovered = [];

        foreach ($this->discoverers as $discoverer) {
            foreach ($discoverer->discover() as $plugin) {
                $this->discovered[$plugin->id()] = $plugin;
            }
        }

        $this->hasDiscovered = true;

        return array_values($this->discovered);
    }

    /** @return Plugin[] */
    public function all(): array
    {
        $this->ensureDiscovered();

        return array_values($this->discovered);
    }

    /** @return Plugin[] */
    public function active(): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (Plugin $p) => $this->isActive($p->id()),
        ));
    }

    public function find(string $id): ?Plugin
    {
        $this->ensureDiscovered();

        return $this->discovered[$id] ?? null;
    }

    public function isActive(string $id): bool
    {
        try {
            return $this->repo->isEnabled($id);
        } catch (\Throwable $e) {
            logger()->warning("PluginManager: DB not ready, defaulting all plugins to disabled. {$e->getMessage()}");
            return false;
        }
    }

    public function install(string $id): void
    {
        $plugin = $this->findOrFail($id);

        $plugin->install();
        $this->repo->markInstalled($id, $plugin->version());
    }

    public function uninstall(string $id): void
    {
        $plugin = $this->findOrFail($id);

        $plugin->uninstall();
        $this->repo->markUninstalled($id);
    }

    public function activate(string $id): void
    {
        $plugin = $this->findOrFail($id);

        $activeIds = array_map(fn (Plugin $p) => $p->id(), $this->active());
        $resolver  = $this->makeResolver();

        if (!$resolver->canActivate($id, $activeIds)) {
            $missing = $resolver->missingDeps($id, $activeIds);
            throw new RuntimeException(
                "Cannot activate plugin [{$id}]: missing dependencies [" . implode(', ', $missing) . "]"
            );
        }

        $plugin->activate();
        $this->repo->setEnabled($id, true, $plugin->version());

        event(new PluginEnabled($id, $plugin));
    }

    public function deactivate(string $id): void
    {
        $plugin = $this->findOrFail($id);

        $activeIds = array_map(fn (Plugin $p) => $p->id(), $this->active());
        $resolver  = $this->makeResolver();

        if (!$resolver->canDeactivate($id, $activeIds)) {
            $dependents = $resolver->dependents($id, $activeIds);
            throw new RuntimeException(
                "Cannot deactivate plugin [{$id}]: still required by [" . implode(', ', $dependents) . "]"
            );
        }

        $plugin->deactivate();
        $this->repo->setEnabled($id, false, $plugin->version());

        event(new PluginDisabled($id, $plugin));
    }

    private function ensureDiscovered(): void
    {
        if (!$this->hasDiscovered) {
            $this->discover();
        }
    }

    private function findOrFail(string $id): Plugin
    {
        $plugin = $this->find($id);

        if (!$plugin) {
            throw new RuntimeException("Plugin [{$id}] not found.");
        }

        return $plugin;
    }

    private function makeResolver(): DependencyResolver
    {
        return new DependencyResolver($this->discovered);
    }
}
