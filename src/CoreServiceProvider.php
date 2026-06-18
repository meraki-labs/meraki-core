<?php

namespace Meraki\Core;

use Meraki\Core\Adapters\LaravelAuthAdapter;
use Meraki\Core\Adapters\LaravelGateAdapter;
use Meraki\Core\Console\Commands\DiscoverCommand;
use Meraki\Core\Console\Commands\DoctorCommand;
use Meraki\Core\Console\Commands\InstallCommand;
use Meraki\Core\Console\Commands\UpdateCommand;
use Meraki\Core\Console\MerakiInfoCommand;
use Meraki\Core\Installer\MerakiInstaller;
use Meraki\Core\Modules\PackageRegistry;
use Meraki\Core\Modules\PermissionRegistry;
use Meraki\Core\Modules\PluginDiscovery;
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

        $this->app->singleton(PluginDiscovery::class, function ($app) {
            return new PluginDiscovery(
                basePath: $app->basePath(),
                cachePath: $app->bootstrapPath('cache/meraki-plugins.php'),
            );
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
            $discovery = $this->app->make(PluginDiscovery::class);
            $packages  = $this->app->make(PackageRegistry::class);

            foreach ($discovery->discover() as $manifest) {
                $configPath = $manifest->basePath . '/config/' . $manifest->config . '.php';
                if (file_exists($configPath)) {
                    $this->mergeConfigFrom($configPath, $manifest->config);
                }

                if (! $packages->has($manifest->id)) {
                    $packages->register($manifest->id, [
                        'provider' => $manifest->provider,
                        'config'   => $manifest->config,
                    ]);
                }
            }

            $registry = $this->app->make(PermissionRegistry::class);

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
            DiscoverCommand::class,
            MerakiInfoCommand::class,
        ]);
    }
}
