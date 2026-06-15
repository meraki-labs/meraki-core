<?php

namespace Meraki\Core;

use Meraki\Core\Adapters\LaravelAuthAdapter;
use Meraki\Core\Adapters\LaravelGateAdapter;
use Meraki\Core\Console\Commands\DoctorCommand;
use Meraki\Core\Console\Commands\InstallCommand;
use Meraki\Core\Console\Commands\UpdateCommand;
use Meraki\Core\Installer\MerakiInstaller;
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

        $this->app->singleton(MerakiInstaller::class, function () {
            return new MerakiInstaller();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/meraki.php' => config_path('meraki.php'),
        ], ['meraki-config']);

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], ['meraki-migrations']);

        $this->app->booted(function () {
            $registry = $this->app->make(PermissionRegistry::class);
            $packages = $this->app->make(PackageRegistry::class);

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

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
            UpdateCommand::class,
            DoctorCommand::class,
        ]);
    }
}
