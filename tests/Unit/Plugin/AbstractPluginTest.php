<?php

namespace Meraki\Core\Tests\Unit\Plugin;

use Orchestra\Testbench\TestCase;
use Meraki\Core\Plugin\AbstractPlugin;
use Meraki\Core\Plugin\PluginMeta;
use Illuminate\Contracts\Foundation\Application;

class AbstractPluginTest extends TestCase
{
    public function test_boot_does_not_throw(): void
    {
        $plugin = $this->makePlugin('test-plugin', null);
        $plugin->boot($this->app);
        $this->assertTrue(true);
    }

    public function test_get_permissions_returns_empty_when_config_is_null(): void
    {
        $plugin = $this->makePlugin('no-config-plugin', null);
        $this->assertSame([], $plugin->getPermissions());
    }

    public function test_get_permissions_reads_from_config_key(): void
    {
        $this->app['config']->set('my-plugin.permissions', [
            ['name' => 'my-plugin.view'],
        ]);

        $plugin = $this->makePlugin('my-plugin', 'my-plugin');
        $permissions = $plugin->getPermissions();

        $this->assertCount(1, $permissions);
        $this->assertSame('my-plugin.view', $permissions[0]['name']);
    }

    public function test_concrete_subclass_only_needs_get_meta_and_register(): void
    {
        $plugin = $this->makePlugin('minimal', null);
        $this->assertInstanceOf(\Meraki\Core\Contracts\PluginInterface::class, $plugin);
    }

    private function makePlugin(string $name, ?string $config): AbstractPlugin
    {
        return new class($name, $config) extends AbstractPlugin {
            public function __construct(
                private string  $pluginName,
                private ?string $pluginConfig,
            ) {}

            public function getMeta(): PluginMeta
            {
                return new PluginMeta(name: $this->pluginName, version: '1.0.0', config: $this->pluginConfig);
            }

            public function register(Application $app): void {}
        };
    }
}
