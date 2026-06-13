<?php

namespace Meraki\Core;

use Meraki\Core\Adapters\LaravelAuthAdapter;
use Meraki\Core\Adapters\LaravelGateAdapter;
use Meraki\Core\Console\MerakiInfoCommand;
use Meraki\Core\CoreManager;
use Meraki\Core\Modules\PackageRegistry;
use Meraki\Core\Modules\PermissionRegistry;
use Meraki\Core\Events\PermissionsRegistered;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/meraki.php',
            'meraki'
        );

        $this->app->singleton(PackageRegistry::class);
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(LaravelAuthAdapter::class);
        $this->app->singleton(LaravelGateAdapter::class);

        $this->app->singleton(CoreManager::class, function ($app) {
            return new CoreManager(
                $app,
                $app->make(PackageRegistry::class),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/meraki.php' => config_path('meraki.php'),
        ], ['meraki-config']);

        if ($this->app->runningInConsole()) {
            $this->commands([MerakiInfoCommand::class]);
        }

        $this->app->booted(function () {
            $registry = $this->app->make(PermissionRegistry::class);
            $packages = $this->app->make(PackageRegistry::class);

            // Collect permissions declared in each registered package's config
            foreach ($packages->all() as $name => $meta) {
                $configKey = $meta['config'] ?? null;
                if ($configKey) {
                    $permissions = config("{$configKey}.permissions", []);
                    if (!empty($permissions)) {
                        $registry->register($permissions);
                    }
                }
            }

            event(new PermissionsRegistered($registry));
        });
    }
}
