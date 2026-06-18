<?php

namespace Meraki\Core\Tests\Models;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Meraki\Core\CoreServiceProvider;
use Meraki\Core\Models\Plugin;
use Orchestra\Testbench\TestCase;

class PluginTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    public function test_meraki_plugins_table_exists_after_migration(): void
    {
        $this->assertTrue(Schema::hasTable('meraki_plugins'));
        $this->assertTrue(Schema::hasColumn('meraki_plugins', 'name'));
        $this->assertTrue(Schema::hasColumn('meraki_plugins', 'meta'));
        $this->assertTrue(Schema::hasColumn('meraki_plugins', 'status'));
        $this->assertTrue(Schema::hasColumn('meraki_plugins', 'installed_at'));
    }

    public function test_scope_active_filters_by_active_status(): void
    {
        Plugin::factory()->create(['status' => Plugin::STATUS_ACTIVE]);
        Plugin::factory()->create(['status' => Plugin::STATUS_INACTIVE]);

        $results = Plugin::active()->get();

        $this->assertCount(1, $results);
        $this->assertEquals(Plugin::STATUS_ACTIVE, $results->first()->status);
    }

    public function test_scope_inactive_filters_by_inactive_status(): void
    {
        Plugin::factory()->create(['status' => Plugin::STATUS_ACTIVE]);
        Plugin::factory()->create(['status' => Plugin::STATUS_INACTIVE]);

        $results = Plugin::inactive()->get();

        $this->assertCount(1, $results);
        $this->assertEquals(Plugin::STATUS_INACTIVE, $results->first()->status);
    }

    public function test_meta_is_cast_to_array(): void
    {
        $plugin = Plugin::factory()->create(['meta' => ['provider' => 'FooServiceProvider']]);

        $this->assertIsArray($plugin->fresh()->meta);
        $this->assertEquals('FooServiceProvider', $plugin->fresh()->meta['provider']);
    }

    public function test_installed_at_is_cast_to_carbon(): void
    {
        $plugin = Plugin::factory()->create(['installed_at' => now()]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $plugin->fresh()->installed_at);
    }

    public function test_is_active_returns_true_for_active_plugin(): void
    {
        $plugin = Plugin::factory()->create(['status' => Plugin::STATUS_ACTIVE]);

        $this->assertTrue($plugin->isActive());
    }

    public function test_is_active_returns_false_for_inactive_plugin(): void
    {
        $plugin = Plugin::factory()->create(['status' => Plugin::STATUS_INACTIVE]);

        $this->assertFalse($plugin->isActive());
    }

    public function test_name_unique_constraint_throws_on_duplicate(): void
    {
        Plugin::factory()->create(['name' => 'duplicate-plugin']);

        $this->expectException(QueryException::class);

        Plugin::factory()->create(['name' => 'duplicate-plugin']);
    }

    public function test_status_constants_are_defined(): void
    {
        $this->assertEquals('active', Plugin::STATUS_ACTIVE);
        $this->assertEquals('inactive', Plugin::STATUS_INACTIVE);
        $this->assertEquals('failed', Plugin::STATUS_FAILED);
    }
}
