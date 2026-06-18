<?php

namespace Meraki\Core\Plugins;

use Illuminate\Contracts\Foundation\Application;
use Meraki\Core\Contracts\Plugin;

abstract class AbstractPlugin implements Plugin
{
    public function description(): string
    {
        return '';
    }

    public function dependencies(): array
    {
        return [];
    }

    public function register(Application $app): void {}

    public function boot(Application $app): void {}

    public function install(): void {}

    public function uninstall(): void {}

    public function activate(): void {}

    public function deactivate(): void {}
}
