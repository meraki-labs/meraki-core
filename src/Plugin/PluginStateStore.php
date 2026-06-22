<?php

namespace Meraki\Core\Plugin;

interface PluginStateStore
{
    public function isEnabled(string $name): bool;

    public function enable(string $name): void;

    public function disable(string $name): void;

    public function isInstalled(string $name): bool;

    public function markInstalled(string $name): void;

    public function markUninstalled(string $name): void;
}
