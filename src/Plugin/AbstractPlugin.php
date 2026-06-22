<?php

namespace Meraki\Core\Plugin;

use Illuminate\Contracts\Foundation\Application;
use Meraki\Core\Contracts\PluginInterface;

abstract class AbstractPlugin implements PluginInterface
{
    abstract public function getMeta(): PluginMeta;

    abstract public function register(Application $app): void;

    public function boot(Application $app): void
    {
        // no-op by default
    }

    public function getPermissions(): array
    {
        $config = $this->getMeta()->config;
        if ($config === null) {
            return [];
        }
        return config("{$config}.permissions", []);
    }

    public function install(Application $app): void {}

    public function uninstall(Application $app): void {}

    public function onEnable(Application $app): void {}

    public function onDisable(Application $app): void {}
}
