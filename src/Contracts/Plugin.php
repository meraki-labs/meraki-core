<?php

namespace Meraki\Core\Contracts;

use Illuminate\Contracts\Foundation\Application;

interface Plugin
{
    public function id(): string;
    public function name(): string;
    public function version(): string;
    public function description(): string;

    /** @return string[] List of plugin IDs this plugin depends on */
    public function dependencies(): array;

    public function register(Application $app): void;
    public function boot(Application $app): void;

    public function install(): void;
    public function uninstall(): void;
    public function activate(): void;
    public function deactivate(): void;
}
