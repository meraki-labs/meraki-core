<?php

namespace Meraki\Core;

use Meraki\Core\Adapters\LaravelAuthAdapter;
use Meraki\Core\Adapters\LaravelGateAdapter;
use Meraki\Core\Console\MerakiInfoCommand;
use Meraki\Core\Console\Commands\DoctorCommand;
use Meraki\Core\Console\Commands\InstallCommand;
use Meraki\Core\Console\Commands\UpdateCommand;
use Meraki\Core\Console\Commands\PluginListCommand;
use Meraki\Core\Console\Commands\PluginEnableCommand;
use Meraki\Core\Console\Commands\PluginDisableCommand;
use Meraki\Core\Console\Commands\PluginInfoCommand;
use Meraki\Core\Events\PluginsBooted;
use Meraki\Core\Installer\MerakiInstaller;
use Meraki\Core\Modules\PackageRegistry;
use Meraki\Core\Modules\PermissionRegistry;
use Meraki\Core\Events\PermissionsRegistered;
use Meraki\Core\Plugins\PluginManager;
use Meraki\Core\Plugins\PluginRepository;
use Meraki\Core\Plugins\Discovery\DirectoryDiscoverer;
use Meraki\Core\Plugins\Discovery\ComposerDiscoverer;
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

        $this->app->singleton(PluginRepository::class);

        $this->app->singleton(PluginManager::class, function ($app) {
            $sources = config('meraki.plugins.discover', ['directory', 'composer']);
            $discoverers = [];

            if (in_array('directory', $sources)) {
                $discoverers[] = new DirectoryDiscoverer(
                    config('meraki.plugins.path', base_path('plugins/'))
                );
            }

            if (in_array('composer', $sources)) {
                $discoverers[] = new ComposerDiscoverer(
                    base_path('vendor/composer/installed.json')
                );
            }

            return new PluginManager($discoverers, $app->make(PluginRepository::class));
        });

        $this->app->singleton(CoreManager::class, function ($app) {
            return new CoreManager(
                $app,
                $app->make(PackageRegistry::class),
                $app->make(PluginManager::class),
            );
        });

        $this->app->singleton(MerakiInstaller::class, function () {
            return new MerakiInstaller();
        });

        // Register enabled plugins early so their service bindings are available
        try {
            $pluginManager = $this->app->make(PluginManager::class);
            foreach ($pluginManager->enabled() as $plugin) {
                $plugin->register($this->app);
            }
        } catch (\Throwable) {
            // DB not ready — plugins default to disabled
        }
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

            // Boot enabled plugins
            try {
                $pluginManager = $this->app->make(PluginManager::class);
                foreach ($pluginManager->enabled() as $plugin) {
                    $plugin->boot($this->app);
                }
                event(new PluginsBooted($pluginManager));
            } catch (\Throwable) {
                // DB not ready — plugins default to disabled
            }
        });

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
            UpdateCommand::class,
            DoctorCommand::class,
            MerakiInfoCommand::class,
            PluginListCommand::class,
            PluginEnableCommand::class,
            PluginDisableCommand::class,
            PluginInfoCommand::class,
        ]);
    }
}
