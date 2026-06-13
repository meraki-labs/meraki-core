<?php
/**
 * @internal
 * Managed by Meraki Core Team
 */

namespace Meraki\Core;

use Meraki\Core\Modules\PermissionRegistry;
use Meraki\Core\Events\PermissionsRegistered;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Container\BindingResolutionException;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/meraki.php',
            'meraki'
        );

        $this->app->singleton(PermissionRegistry::class);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/meraki.php' => config_path('meraki.php'),
        ], ['meraki-config']);

        // Fire lifecycle event for IAM / others
        event(new PermissionsRegistered(
            $this->app->make(PermissionRegistry::class)
        ));
    }
}