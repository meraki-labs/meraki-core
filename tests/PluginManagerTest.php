<?php

namespace Meraki\Core\Tests;

use Orchestra\Testbench\TestCase;
use Meraki\Core\CoreServiceProvider;
use Meraki\Core\Contracts\Plugin;
use Meraki\Core\Events\PluginEnabled;
use Meraki\Core\Events\PluginDisabled;
use Meraki\Core\Events\PluginsBooted;
use Meraki\Core\Plugins\AbstractPlugin;
use Meraki\Core\Plugins\PluginManager;
use Meraki\Core\Plugins\PluginRepository;
use Meraki\Core\Plugins\Discovery\PluginDiscoverer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;

class PluginManagerTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    private function makeManager(array $plugins = []): PluginManager
    {
        $discoverer = new class($plugins) implements PluginDiscoverer {
            public function __construct(private array $plugins) {}
            public function discover(): array { return $this->plugins; }
        };

        return new PluginManager([$discoverer], $this->app->make(PluginRepository::class));
    }

    private function makePlugin(string $id, string $name = 'Test Plugin'): Plugin
    {
        return new class($id, $name) extends AbstractPlugin {
            public function __construct(private string $pluginId, private string $pluginName) {}
            public function id(): string      { return $this->pluginId; }
            public function name(): string    { return $this->pluginName; }
            public function version(): string { return '1.0.0'; }
        };
    }

    public function test_discover_returns_all_plugins(): void
    {
        $pluginA = $this->makePlugin('plugin-a');
        $pluginB = $this->makePlugin('plugin-b');
        $manager = $this->makeManager([$pluginA, $pluginB]);

        $discovered = $manager->discover();

        $this->assertCount(2, $discovered);
        $ids = array_map(fn (Plugin $p) => $p->id(), $discovered);
        $this->assertContains('plugin-a', $ids);
        $this->assertContains('plugin-b', $ids);
    }

    public function test_plugin_disabled_by_default(): void
    {
        $plugin  = $this->makePlugin('my-plugin');
        $manager = $this->makeManager([$plugin]);

        $this->assertFalse($manager->isEnabled('my-plugin'));
    }

    public function test_enable_persists_state(): void
    {
        $plugin  = $this->makePlugin('my-plugin');
        $manager = $this->makeManager([$plugin]);

        $manager->enable('my-plugin');

        $this->assertTrue($manager->isEnabled('my-plugin'));
    }

    public function test_disable_persists_state(): void
    {
        $plugin  = $this->makePlugin('my-plugin');
        $manager = $this->makeManager([$plugin]);

        $manager->enable('my-plugin');
        $manager->disable('my-plugin');

        $this->assertFalse($manager->isEnabled('my-plugin'));
    }

    public function test_enabled_returns_only_enabled_plugins(): void
    {
        $pluginA = $this->makePlugin('plugin-a');
        $pluginB = $this->makePlugin('plugin-b');
        $manager = $this->makeManager([$pluginA, $pluginB]);

        $manager->enable('plugin-a');

        $enabled = $manager->enabled();
        $this->assertCount(1, $enabled);
        $this->assertSame('plugin-a', $enabled[0]->id());
    }

    public function test_find_returns_plugin_by_id(): void
    {
        $plugin  = $this->makePlugin('target-plugin');
        $manager = $this->makeManager([$plugin]);

        $found = $manager->find('target-plugin');

        $this->assertNotNull($found);
        $this->assertSame('target-plugin', $found->id());
    }

    public function test_find_returns_null_for_unknown_id(): void
    {
        $manager = $this->makeManager([]);

        $this->assertNull($manager->find('unknown'));
    }

    public function test_enable_fires_plugin_enabled_event(): void
    {
        Event::fake([PluginEnabled::class]);

        $plugin  = $this->makePlugin('evt-plugin');
        $manager = $this->makeManager([$plugin]);

        $manager->enable('evt-plugin');

        Event::assertDispatched(PluginEnabled::class, function (PluginEnabled $e) {
            return $e->id === 'evt-plugin';
        });
    }

    public function test_disable_fires_plugin_disabled_event(): void
    {
        Event::fake([PluginDisabled::class]);

        $plugin  = $this->makePlugin('evt-plugin');
        $manager = $this->makeManager([$plugin]);

        $manager->enable('evt-plugin');
        $manager->disable('evt-plugin');

        Event::assertDispatched(PluginDisabled::class, function (PluginDisabled $e) {
            return $e->id === 'evt-plugin';
        });
    }

    public function test_enable_unknown_plugin_throws(): void
    {
        $manager = $this->makeManager([]);

        $this->expectException(\RuntimeException::class);
        $manager->enable('non-existent');
    }

    public function test_disable_unknown_plugin_throws(): void
    {
        $manager = $this->makeManager([]);

        $this->expectException(\RuntimeException::class);
        $manager->disable('non-existent');
    }

    public function test_plugins_method_on_core_manager_returns_plugin_manager(): void
    {
        $coreManager = $this->app->make(\Meraki\Core\CoreManager::class);

        $this->assertInstanceOf(PluginManager::class, $coreManager->plugins());
    }
}
