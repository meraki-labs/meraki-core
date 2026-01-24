<?php

namespace Meraki\Core;

use Illuminate\Support\ServiceProvider;

/**
 * @Author DatPA
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/meraki.php', 'meraki');
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        // Publish
        $this->publishes([
            __DIR__ . '/../config/meraki.php' => config_path('meraki.php'),
        ], ['meraki-config', ['meraki-core']]);
    }
}