<?php

namespace Meraki\Core\Tests;

use Orchestra\Testbench\TestCase;
use Meraki\Core\CoreServiceProvider;
use Meraki\Core\Modules\PackageRegistry;
use Meraki\Core\Modules\PermissionRegistry;
use Meraki\Core\Modules\PluginRegistry;
use Meraki\Core\Plugin\AbstractPlugin;
use Meraki\Core\Plugin\PluginMeta;
use Illuminate\Contracts\Foundation\Application;

class PluginLifecycleTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class, FakePluginServiceProvider::class, FakeLegacyPackageServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('fake-plugin.permissions', [
            ['module' => 'fake-plugin', 'name' => 'fake-plugin.view', 'label' => 'View Fake Plugin'],
        ]);

        $app['config']->set('fake-legacy.permissions', [
            ['module' => 'fake-legacy', 'name' => 'fake-legacy.edit', 'label' => 'Edit Fake Legacy'],
        ]);
    }

    public function test_typed_plugin_permissions_are_loaded_after_booted(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);
        $names    = array_column($registry->all(), 'name');

        $this->assertContains('fake-plugin.view', $names);
    }

    public function test_legacy_package_permissions_still_load_after_booted(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);
        $names    = array_column($registry->all(), 'name');

        $this->assertContains('fake-legacy.edit', $names);
    }

    public function test_plugin_boot_is_called_once(): void
    {
        $plugin = $this->app->make(PluginRegistry::class)->get('fake-plugin');
        $this->assertInstanceOf(FakePlugin::class, $plugin);
        $this->assertSame(1, $plugin->bootCallCount);
    }

    public function test_plugin_registry_is_independent_of_package_registry(): void
    {
        $pluginRegistry  = $this->app->make(PluginRegistry::class);
        $packageRegistry = $this->app->make(PackageRegistry::class);

        $this->assertTrue($pluginRegistry->has('fake-plugin'));
        $this->assertFalse($packageRegistry->has('fake-plugin'));

        $this->assertTrue($packageRegistry->has('fake-legacy'));
        $this->assertFalse($pluginRegistry->has('fake-legacy'));
    }

    public function test_plugin_registry_singleton_resolves(): void
    {
        $a = $this->app->make(PluginRegistry::class);
        $b = $this->app->make(PluginRegistry::class);
        $this->assertSame($a, $b);
    }
}

class FakePlugin extends AbstractPlugin
{
    public int $bootCallCount = 0;

    public function getMeta(): PluginMeta
    {
        return new PluginMeta(
            name:    'fake-plugin',
            version: '1.0.0',
            config:  'fake-plugin',
        );
    }

    public function register(Application $app): void {}

    public function boot(Application $app): void
    {
        $this->bootCallCount++;
    }
}

class FakePluginServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        $plugin = new FakePlugin();
        $plugin->register($this->app);
        $this->app->make(PluginRegistry::class)->register($plugin);
    }
}

class FakeLegacyPackageServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        $this->app->make(PackageRegistry::class)->register('fake-legacy', ['config' => 'fake-legacy']);
    }
}
