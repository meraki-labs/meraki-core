<?php

namespace Meraki\Core\Tests\Integration;

use Illuminate\Contracts\Foundation\Application;
use Meraki\Core\CoreManager;
use Meraki\Core\CoreServiceProvider;
use Meraki\Core\Modules\PermissionRegistry;
use Meraki\Core\Modules\PluginRegistry;
use Meraki\Core\Plugin\AbstractPlugin;
use Meraki\Core\Plugin\PluginLoader;
use Meraki\Core\Plugin\PluginMeta;
use Meraki\Core\Plugin\PluginStateStore;
use Orchestra\Testbench\TestCase;

class PluginLoaderLifecycleTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_plugin_loader_is_accessible_via_core_manager(): void
    {
        $manager = $this->app->make(CoreManager::class);

        $this->assertInstanceOf(PluginLoader::class, $manager->plugins());
    }

    public function test_plugin_loader_singleton_from_container(): void
    {
        $a = $this->app->make(PluginLoader::class);
        $b = $this->app->make(PluginLoader::class);

        $this->assertSame($a, $b);
    }

    public function test_manually_registered_plugin_is_present_in_registry(): void
    {
        // Manually register a PluginInterface plugin via PluginRegistry (legacy style).
        // The PluginLoader::loaded() should include it since it shares the same PluginRegistry.
        $plugin = new FakeLoadablePlugin();
        $this->app->make(PluginRegistry::class)->register($plugin);

        $loader = $this->app->make(PluginLoader::class);

        $this->assertSame($plugin, $loader->get('test-plugin'));
    }

    public function test_disabled_plugin_is_not_loaded_by_load_all_enabled(): void
    {
        config(['meraki.plugins.disabled' => ['test-plugin']]);
        config(['meraki.plugins.list' => ['test-plugin' => FakeLoadablePlugin::class]]);

        $loader = $this->app->make(PluginLoader::class);
        $loader->discover();
        $loader->loadAllEnabled();

        $this->assertNull($loader->get('test-plugin'));
    }

    public function test_enabled_plugin_is_loaded_by_load_all_enabled(): void
    {
        config(['meraki.plugins.list' => ['test-plugin' => FakeLoadablePlugin::class]]);
        config(['meraki.plugins.disabled' => []]);

        $loader = $this->app->make(PluginLoader::class);
        $loader->discover();
        $loader->loadAllEnabled();

        $this->assertNotNull($loader->get('test-plugin'));
    }

    public function test_plugin_permissions_are_registered_after_boot(): void
    {
        config(['meraki.plugins.list' => ['perm-plugin' => FakePermissionPlugin::class]]);
        config(['meraki.plugins.disabled' => []]);
        config(['fake-perm-plugin.permissions' => [
            ['module' => 'perm-plugin', 'name' => 'perm-plugin.read', 'label' => 'Read'],
        ]]);

        // The loader and registry are already created. We need to trigger discover
        // and load in a fresh app context. We directly test the flow:
        $registry = $this->app->make(PluginRegistry::class);
        $loader   = $this->app->make(PluginLoader::class);
        $loader->discover();
        $loader->loadAllEnabled();

        foreach ($loader->loaded() as $plugin) {
            $plugin->boot($this->app);
            $perms = $plugin->getPermissions();
            if (!empty($perms)) {
                $this->app->make(PermissionRegistry::class)->register($perms);
            }
        }

        $names = array_column($this->app->make(PermissionRegistry::class)->all(), 'name');
        $this->assertContains('perm-plugin.read', $names);
    }

    public function test_legacy_packages_are_not_affected(): void
    {
        $packages = $this->app->make(\Meraki\Core\Modules\PackageRegistry::class);
        $packages->register('legacy', ['config' => 'legacy-cfg']);

        $this->assertTrue($packages->has('legacy'));
        $this->assertFalse($this->app->make(PluginRegistry::class)->has('legacy'));
    }
}

class FakeLoadablePlugin extends AbstractPlugin
{
    public function getMeta(): PluginMeta
    {
        return new PluginMeta(name: 'test-plugin', version: '1.0.0');
    }

    public function register(Application $app): void {}
}

class FakePermissionPlugin extends AbstractPlugin
{
    public function getMeta(): PluginMeta
    {
        return new PluginMeta(name: 'perm-plugin', version: '1.0.0', config: 'fake-perm-plugin');
    }

    public function register(Application $app): void {}
}
