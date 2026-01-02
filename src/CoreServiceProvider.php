<?php

namespace Meraki\Core;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/core.php',
            'meraki'
        );
    }

    public function boot(): void
    {
        // Publish
        $this->publishes([
            __DIR__ . '/../config/core.php' => config_path('meraki/core.php'),
        ], 'meraki-config');
    }
}