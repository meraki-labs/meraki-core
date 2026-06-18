<?php

namespace Meraki\Core\Tests\Unit\Plugin;

use Illuminate\Contracts\Foundation\Application;
use Meraki\Core\Modules\PluginRegistry;
use Meraki\Core\Plugin\AbstractPlugin;
use Meraki\Core\Plugin\PluginDiscovery;
use Meraki\Core\Plugin\PluginLoader;
use Meraki\Core\Plugin\PluginMeta;
use Meraki\Core\Plugin\PluginStateStore;
use Orchestra\Testbench\TestCase;
use Meraki\Core\CoreServiceProvider;

class PluginLoaderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeLoader(
        array $discovered = [],
        ?PluginStateStore $state = null
    ): PluginLoader {
        $discovery = new class($discovered) extends PluginDiscovery {
            public function __construct(private array $map) {}

            public function all(): array
            {
                return $this->map;
            }
        };

        $registry = new PluginRegistry();
        $state    = $state ?? $this->app->make(PluginStateStore::class);

        $loader = new PluginLoader($this->app, $discovery, $registry, $state);
        $loader->discover();

        return $loader;
    }

    private function alwaysEnabledState(): PluginStateStore
    {
        return new class implements PluginStateStore {
            public function isEnabled(string $name): bool    { return true; }
            public function enable(string $name): void       {}
            public function disable(string $name): void      {}
            public function isInstalled(string $name): bool  { return false; }
            public function markInstalled(string $name): void {}
            public function markUninstalled(string $name): void {}
        };
    }

    private function stateEnabledOnly(string ...$names): PluginStateStore
    {
        return new class($names) implements PluginStateStore {
            public function __construct(private array $enabled) {}
            public function isEnabled(string $name): bool    { return in_array($name, $this->enabled); }
            public function enable(string $name): void       {}
            public function disable(string $name): void      {}
            public function isInstalled(string $name): bool  { return false; }
            public function markInstalled(string $name): void {}
            public function markUninstalled(string $name): void {}
        };
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_discover_populates_discovered_map(): void
    {
        $loader = $this->makeLoader(['my-plugin' => \stdClass::class]);

        $this->assertSame(['my-plugin' => \stdClass::class], $loader->discovered());
    }

    public function test_load_instantiates_and_registers_plugin(): void
    {
        $this->app->bind(LoaderTestPluginA::class, fn () => new LoaderTestPluginA());

        $loader = $this->makeLoader(['plugin-a' => LoaderTestPluginA::class], $this->alwaysEnabledState());
        $loaded = $loader->load('plugin-a');

        $this->assertInstanceOf(LoaderTestPluginA::class, $loaded);
        $this->assertInstanceOf(LoaderTestPluginA::class, $loader->get('plugin-a'));
    }

    public function test_load_is_idempotent(): void
    {
        $this->app->bind(LoaderTestPluginA::class, fn () => new LoaderTestPluginA());

        $loader = $this->makeLoader(['plugin-a' => LoaderTestPluginA::class], $this->alwaysEnabledState());
        $first  = $loader->load('plugin-a');
        $second = $loader->load('plugin-a');

        $this->assertSame($first, $second);
    }

    public function test_load_throws_for_undiscovered_plugin(): void
    {
        $loader = $this->makeLoader([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not discovered/');

        $loader->load('unknown-plugin');
    }

    public function test_load_all_enabled_loads_only_enabled_plugins(): void
    {
        $this->app->bind(LoaderTestPluginA::class, fn () => new LoaderTestPluginA());
        $this->app->bind(LoaderTestPluginB::class, fn () => new LoaderTestPluginB());

        $loader = $this->makeLoader(
            ['plugin-a' => LoaderTestPluginA::class, 'plugin-b' => LoaderTestPluginB::class],
            $this->stateEnabledOnly('plugin-a'),
        );

        $loader->loadAllEnabled();

        $this->assertInstanceOf(LoaderTestPluginA::class, $loader->get('plugin-a'));
        $this->assertNull($loader->get('plugin-b'));
    }

    public function test_enable_calls_on_enable_hook(): void
    {
        $this->app->bind(LoaderTestPluginA::class, fn () => new LoaderTestPluginA());

        $loader = $this->makeLoader(['plugin-a' => LoaderTestPluginA::class], $this->alwaysEnabledState());
        $loader->enable('plugin-a');

        $this->assertTrue(LoaderTestPluginA::$onEnableCalled);
    }

    public function test_disable_calls_on_disable_hook_for_loaded_plugin(): void
    {
        $this->app->bind(LoaderTestPluginA::class, fn () => new LoaderTestPluginA());

        $loader = $this->makeLoader(['plugin-a' => LoaderTestPluginA::class], $this->alwaysEnabledState());
        $loader->load('plugin-a');
        $loader->disable('plugin-a');

        $this->assertTrue(LoaderTestPluginA::$onDisableCalled);
    }

    public function test_install_calls_install_hook_and_marks_installed(): void
    {
        $this->app->bind(LoaderTestPluginA::class, fn () => new LoaderTestPluginA());

        $loader = $this->makeLoader(['plugin-a' => LoaderTestPluginA::class]);
        $loader->install('plugin-a');

        $this->assertTrue(LoaderTestPluginA::$installCalled);
        $this->assertTrue($loader->isInstalled('plugin-a'));
    }

    public function test_loaded_returns_all_loaded_plugins(): void
    {
        $this->app->bind(LoaderTestPluginA::class, fn () => new LoaderTestPluginA());

        $loader = $this->makeLoader(['plugin-a' => LoaderTestPluginA::class], $this->alwaysEnabledState());
        $loader->loadAllEnabled();

        $this->assertCount(1, $loader->loaded());
        $this->assertInstanceOf(LoaderTestPluginA::class, array_values($loader->loaded())[0]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        LoaderTestPluginA::reset();
        LoaderTestPluginB::reset();
    }
}

class LoaderTestPluginA extends AbstractPlugin
{
    public static bool $installCalled  = false;
    public static bool $onEnableCalled  = false;
    public static bool $onDisableCalled = false;

    public static function reset(): void
    {
        self::$installCalled  = false;
        self::$onEnableCalled  = false;
        self::$onDisableCalled = false;
    }

    public function getMeta(): PluginMeta
    {
        return new PluginMeta(name: 'plugin-a', version: '1.0.0');
    }

    public function register(Application $app): void {}

    public function install(Application $app): void
    {
        self::$installCalled = true;
    }

    public function onEnable(Application $app): void
    {
        self::$onEnableCalled = true;
    }

    public function onDisable(Application $app): void
    {
        self::$onDisableCalled = true;
    }
}

class LoaderTestPluginB extends AbstractPlugin
{
    public static bool $bootCalled = false;

    public static function reset(): void
    {
        self::$bootCalled = false;
    }

    public function getMeta(): PluginMeta
    {
        return new PluginMeta(name: 'plugin-b', version: '1.0.0');
    }

    public function register(Application $app): void {}

    public function boot(Application $app): void
    {
        self::$bootCalled = true;
    }
}
