<?php

namespace Meraki\Core\Contracts;

use Illuminate\Contracts\Foundation\Application;
use Meraki\Core\Plugin\PluginMeta;

interface PluginInterface
{
    public function getMeta(): PluginMeta;

    /**
     * Bind plugin services into the container.
     * MUST be called during the register() phase of a ServiceProvider — not in booted().
     */
    public function register(Application $app): void;

    public function boot(Application $app): void;

    public function getPermissions(): array;
}
