<?php

namespace Meraki\Core;

use Meraki\Core\CoreManager;
use Meraki\Core\Adapters\LaravelAuthAdapter;
use Meraki\Core\Adapters\LaravelGateAdapter;
use Meraki\Core\Console\Commands\DiscoverCommand;
use Meraki\Core\Console\Commands\DoctorCommand;
use Meraki\Core\Console\Commands\InfoCommand;
use Meraki\Core\Console\Commands\InstallCommand;
use Meraki\Core\Console\Commands\UpdateCommand;
use Meraki\Core\Console\Commands\Plugin\ListPluginsCommand;
use Meraki\Core\Console\Commands\Plugin\EnablePluginCommand;
use Meraki\Core\Console\Commands\Plugin\DisablePluginCommand;
use Meraki\Core\Console\Commands\Plugin\InstallPluginCommand;
use Meraki\Core\Console\Commands\Plugin\UninstallPluginCommand;
use Meraki\Core\Events\PluginsBooted;
use Meraki\Core\Hooks\HookRegistry;
use Meraki\Core\Installer\MerakiInstaller;
use Meraki\Core\Installer\PluginInstaller;
use Meraki\Core\Modules\DependencyGraph;
use Meraki\Core\Modules\DependencyResolver;
use Meraki\Core\Modules\PackageRegistry;
use Meraki\Core\Modules\PermissionRegistry;
use Meraki\Core\Modules\PluginRegistry;
use Meraki\Core\Modules\PluginDiscovery as ManifestPluginDiscovery;
use Meraki\Core\Plugin\DatabaseStateStore;
use Meraki\Core\Plugin\PluginDiscovery;
use Meraki\Core\Plugin\PluginLoader;
use Meraki\Core\Plugin\PluginStateStore;
use Meraki\Core\Events\PermissionsRegistered;
use Meraki\Core\Exceptions\MissingDependencyException;
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

        // PluginLoader (1.6) — orchestrator for PluginInterface-based plugins
        $this->app->singleton(PluginDiscovery::class);
        $this->app->singleton(PluginStateStore::class, DatabaseStateStore::class);
        $this->app->singleton(PluginLoader::class, function ($app) {
            return new PluginLoader(
                $app,
                $app->make(PluginDiscovery::class),
                $app->make(PluginRegistry::class),
                $app->make(PluginStateStore::class),
            );
        });

        // Discovery runs early (only reads files, no instantiation)
        try {
            $this->app->make(PluginLoader::class)->discover();
        } catch (\Throwable) {
            // silently skip if discovery fails (e.g. composer files not present)
        }

        $this->app->singleton(CoreManager::class, function ($app) {
            return new CoreManager(
                $app,
                $app->make(PackageRegistry::class),
                $app->make(PluginLoader::class),
                $app->make(HookRegistry::class),
            );
        });

        $this->app->singleton(MerakiInstaller::class, function () {
            return new MerakiInstaller();
        });

        $this->app->singleton(PluginInstaller::class, function ($app) {
            return new PluginInstaller(
                pluginsBasePath: base_path('plugins'),
                tempPath: sys_get_temp_dir(),
                pluginDiscovery: $app->make(PluginDiscovery::class),
            );
        });

        $this->app->alias(CoreManager::class, 'meraki');

        // Register enabled Plugin-interface plugins early so their bindings are available
        try {
            $pluginManager = $this->app->make(PluginManager::class);
            foreach ($pluginManager->active() as $plugin) {
                $plugin->register($this->app);
            }
        } catch (\Throwable) {
            // DB not ready — plugins default to disabled
        }

        $this->app->singleton(ManifestPluginDiscovery::class, function ($app) {
            return new ManifestPluginDiscovery(
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

        $this->app->booted(function () {
            $manifestDiscovery = $this->app->make(ManifestPluginDiscovery::class);
            $packages          = $this->app->make(PackageRegistry::class);
            $plugins           = $this->app->make(PluginRegistry::class);
            $loader            = $this->app->make(PluginLoader::class);
            $registry          = $this->app->make(PermissionRegistry::class);

            foreach ($manifestDiscovery->discover() as $manifest) {
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

                // Register PSR-4 autoloader for directory-based plugins that ship their own classes.
                // Must run after Core boots so plugin providers are resolved correctly.
                if ($manifest->source === 'plugins' && !empty($manifest->autoload['psr4'])) {
                    foreach ($manifest->autoload['psr4'] as $namespace => $relativePath) {
                        $fullPath = $manifest->basePath . '/' . ltrim($relativePath, '/');
                        spl_autoload_register(function (string $class) use ($namespace, $fullPath) {
                            if (!str_starts_with($class, $namespace)) {
                                return;
                            }
                            $file = $fullPath . str_replace('\\', '/', substr($class, strlen($namespace))) . '.php';
                            if (file_exists($file)) {
                                require_once $file;
                            }
                        });
                    }
                }
            }

            // Load all enabled PluginInterface plugins into registry
            try {
                $loader->loadAllEnabled();
            } catch (\Throwable) {
                // DB not ready — skip auto-load
            }

            // Boot all plugins in registry (manually registered + loader-discovered)
            foreach ($plugins->all() as $plugin) {
                $plugin->boot($this->app);
                $permissions = $plugin->getPermissions();
                if (!empty($permissions)) {
                    $registry->register($permissions);
                }
            }

            // Legacy array packages
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

            // Boot Plugin-interface plugins (PluginManager system)
            try {
                $pluginManager = $this->app->make(PluginManager::class);
                foreach ($pluginManager->active() as $plugin) {
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
            ListPluginsCommand::class,
            EnablePluginCommand::class,
            DisablePluginCommand::class,
            InstallPluginCommand::class,
            UninstallPluginCommand::class,
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
