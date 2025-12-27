<?php

namespace Merakilab\Core;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/meraki.php',
            'meraki'
        );
    }

    public function boot(): void
    {
        // Routes
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        // Views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'meraki');

        // Publish
        $this->publishes([
            __DIR__.'/config/meraki.php' => config_path('meraki.php'),
        ], 'meraki-config');
    }
}