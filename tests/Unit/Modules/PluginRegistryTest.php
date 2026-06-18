<?php

namespace Meraki\Core\Tests\Unit\Modules;

use PHPUnit\Framework\TestCase;
use Meraki\Core\Modules\PluginRegistry;
use Meraki\Core\Modules\PackageRegistry;
use Meraki\Core\Contracts\PluginInterface;
use Meraki\Core\Plugin\AbstractPlugin;
use Meraki\Core\Plugin\PluginMeta;
use Illuminate\Contracts\Foundation\Application;

class PluginRegistryTest extends TestCase
{
    private PluginRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new PluginRegistry();
    }

    public function test_register_stores_plugin_keyed_by_name(): void
    {
        $plugin = $this->makePlugin('meraki-auth');
        $this->registry->register($plugin);

        $this->assertTrue($this->registry->has('meraki-auth'));
    }

    public function test_all_returns_registered_plugins(): void
    {
        $this->registry->register($this->makePlugin('meraki-auth'));
        $this->registry->register($this->makePlugin('meraki-cms'));

        $all = $this->registry->all();
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('meraki-auth', $all);
        $this->assertArrayHasKey('meraki-cms', $all);
    }

    public function test_has_returns_false_for_unregistered_plugin(): void
    {
        $this->assertFalse($this->registry->has('does-not-exist'));
    }

    public function test_get_returns_plugin_instance(): void
    {
        $plugin = $this->makePlugin('meraki-auth');
        $this->registry->register($plugin);

        $this->assertSame($plugin, $this->registry->get('meraki-auth'));
    }

    public function test_get_returns_null_for_unregistered_plugin(): void
    {
        $this->assertNull($this->registry->get('unknown'));
    }

    public function test_same_name_overwrites_previous_registration(): void
    {
        $first  = $this->makePlugin('meraki-auth');
        $second = $this->makePlugin('meraki-auth');

        $this->registry->register($first);
        $this->registry->register($second);

        $this->assertSame($second, $this->registry->get('meraki-auth'));
        $this->assertCount(1, $this->registry->all());
    }

    public function test_does_not_reference_package_registry(): void
    {
        $source = file_get_contents(__DIR__ . '/../../../src/Modules/PluginRegistry.php');
        $this->assertStringNotContainsString('PackageRegistry', $source);
    }

    private function makePlugin(string $name): PluginInterface
    {
        return new class($name) extends AbstractPlugin {
            public function __construct(private string $pluginName) {}

            public function getMeta(): PluginMeta
            {
                return new PluginMeta(name: $this->pluginName, version: '1.0.0');
            }

            public function register(Application $app): void {}
        };
    }
}
