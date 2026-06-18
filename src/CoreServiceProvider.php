<?php

namespace Meraki\Core;

use Meraki\Core\CoreManager;
use Meraki\Core\Adapters\LaravelAuthAdapter;
use Meraki\Core\Adapters\LaravelGateAdapter;
use Meraki\Core\Console\Commands\DiscoverCommand;
use Meraki\Core\Console\MerakiInfoCommand;
use Meraki\Core\Console\Commands\DoctorCommand;
use Meraki\Core\Console\Commands\InfoCommand;
use Meraki\Core\Console\Commands\InstallCommand;
use Meraki\Core\Console\Commands\UpdateCommand;
use Meraki\Core\Exceptions\MissingDependencyException;
use Meraki\Core\Console\Commands\PluginListCommand;
use Meraki\Core\Console\Commands\PluginEnableCommand;
use Meraki\Core\Console\Commands\PluginDisableCommand;
use Meraki\Core\Console\Commands\PluginInfoCommand;
use Meraki\Core\Events\PluginsBooted;
use Meraki\Core\Console\MerakiInfoCommand;
use Meraki\Core\Hooks\HookRegistry;
use Meraki\Core\Installer\MerakiInstaller;
use Meraki\Core\Modules\DependencyGraph;
use Meraki\Core\Modules\DependencyResolver;
use Meraki\Core\Modules\PackageRegistry;
use Meraki\Core\Modules\PermissionRegistry;
use Meraki\Core\Modules\PluginRegistry;
use Meraki\Core\Modules\PluginDiscovery;
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
        $this->mergeConfigDeep(
            __DIR__ . '/../config/meraki.php',
            'meraki'
        );

        $this->app->singleton(PackageRegistry::class);
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(PluginRegistry::class);
        $this->app->singleton(HookRegistry::class);
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
                $app->make(HookRegistry::class),
            );
        });

        $this->app->singleton(MerakiInstaller::class, function () {
            return new MerakiInstaller();
        });

        $this->app->alias(CoreManager::class, 'meraki');

        // Register enabled plugins early so their service bindings are available
        try {
            $pluginManager = $this->app->make(PluginManager::class);
            foreach ($pluginManager->enabled() as $plugin) {
                $plugin->register($this->app);
            }
        } catch (\Throwable) {
            // DB not ready — plugins default to disabled
        }

        $this->app->singleton(PluginDiscovery::class, function ($app) {
            return new PluginDiscovery(
                basePath: $app->basePath(),
                cachePath: $app->bootstrapPath('cache/meraki-plugins.php'),
            );
        });
    }

    protected function mergeConfigDeep(string $path, string $key): void
    {
        $existing = config($key, []);
        $default = require $path;
        config([$key => array_replace_recursive($default, $existing)]);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/meraki.php' => config_path('meraki.php'),
        ], ['meraki-config']);

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], ['meraki-migrations']);

        $this->validateDependencies();

        if ($this->app->runningInConsole()) {
            $this->commands([MerakiInfoCommand::class]);
        }

        $this->app->booted(function () {
            $discovery = $this->app->make(PluginDiscovery::class);
            $packages  = $this->app->make(PackageRegistry::class);
            $plugins  = $this->app->make(PluginRegistry::class);

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

            // --- Typed plugins (PluginRegistry, độc lập) ---
            foreach ($plugins->all() as $plugin) {
                $plugin->boot($this->app);
                $permissions = $plugin->getPermissions();
                if (!empty($permissions)) {
                    $registry->register($permissions);
                }
            }

            // --- Legacy array packages (PackageRegistry, giữ nguyên) ---
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
            DiscoverCommand::class,
            MerakiInfoCommand::class,
            PluginListCommand::class,
            PluginEnableCommand::class,
            PluginDisableCommand::class,
            PluginInfoCommand::class,
            InfoCommand::class,
        ]);
    }

    protected function validateDependencies(): void
    {
        $packages = $this->app->make(PackageRegistry::class);
        $graph    = $this->buildDependencyGraph($packages);
        $resolver = new DependencyResolver();
        $resolver->resolve($graph);
    }

    protected function buildDependencyGraph(PackageRegistry $packages): DependencyGraph
    {
        $graph = new DependencyGraph();
        $all   = $packages->all();

        foreach ($all as $name => $meta) {
            $graph->addNode($name);
        }

        foreach ($all as $name => $meta) {
            foreach ($meta['requires'] ?? [] as $dep) {
                if (!array_key_exists($dep, $all)) {
                    throw new MissingDependencyException($dep, $name);
                }
                $graph->addEdge($name, $dep);
            }
        }

        return $graph;
    }
}
