<?php

namespace Meraki\Core\Tests\Unit\Plugin;

use Meraki\Core\Plugin\DatabaseStateStore;
use Orchestra\Testbench\TestCase;
use Meraki\Core\CoreServiceProvider;

class DatabaseStateStoreTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');
    }

    private function store(): DatabaseStateStore
    {
        return new DatabaseStateStore();
    }

    public function test_is_enabled_defaults_to_true_when_no_record(): void
    {
        $this->assertTrue($this->store()->isEnabled('my-plugin'));
    }

    public function test_enable_persists_state(): void
    {
        $store = $this->store();
        $store->enable('my-plugin');

        $this->assertTrue($store->isEnabled('my-plugin'));
    }

    public function test_disable_persists_state(): void
    {
        $store = $this->store();
        $store->disable('my-plugin');

        $this->assertFalse($store->isEnabled('my-plugin'));
    }

    public function test_enable_after_disable_re_enables(): void
    {
        $store = $this->store();
        $store->disable('my-plugin');
        $store->enable('my-plugin');

        $this->assertTrue($store->isEnabled('my-plugin'));
    }

    public function test_config_disabled_array_overrides_db_enabled(): void
    {
        $store = $this->store();
        $store->enable('my-plugin');

        config(['meraki.plugins.disabled' => ['my-plugin']]);

        $this->assertFalse($store->isEnabled('my-plugin'));
    }

    public function test_is_installed_defaults_to_false(): void
    {
        $this->assertFalse($this->store()->isInstalled('my-plugin'));
    }

    public function test_mark_installed_persists(): void
    {
        $store = $this->store();
        $store->markInstalled('my-plugin');

        $this->assertTrue($store->isInstalled('my-plugin'));
    }

    public function test_mark_uninstalled_clears_installed(): void
    {
        $store = $this->store();
        $store->markInstalled('my-plugin');
        $store->markUninstalled('my-plugin');

        $this->assertFalse($store->isInstalled('my-plugin'));
    }

    public function test_state_is_scoped_per_plugin_name(): void
    {
        $store = $this->store();
        $store->disable('plugin-a');
        $store->markInstalled('plugin-b');

        $this->assertFalse($store->isEnabled('plugin-a'));
        $this->assertTrue($store->isEnabled('plugin-b'));
        $this->assertFalse($store->isInstalled('plugin-a'));
        $this->assertTrue($store->isInstalled('plugin-b'));
    }
}
