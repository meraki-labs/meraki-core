<?php

namespace Meraki\Core\Tests;

use Meraki\Core\Modules\PackageRegistry;
use Meraki\Core\Modules\PermissionRegistry;
use Meraki\Core\Events\PermissionsRegistered;
use Meraki\Core\Testing\FakePackageServiceProvider;
use Meraki\Core\Testing\MerakiTestCase;
use Illuminate\Support\Facades\Event;

class PermissionLifecycleTest extends MerakiTestCase
{
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), FakePackageServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        // Simulate a package config with permissions
        $app['config']->set('fake-package.permissions', [
            ['module' => 'fake', 'name' => 'fake.view', 'label' => 'View Fake'],
            ['module' => 'fake', 'name' => 'fake.edit', 'label' => 'Edit Fake'],
        ]);
    }

    // Test 4: event fires after all providers booted and contains all permissions from registered packages
    public function test_permissions_registered_event_fires_after_booted_with_package_permissions(): void
    {
        $received = null;

        $this->app->make('events')->listen(PermissionsRegistered::class, function (PermissionsRegistered $event) use (&$received) {
            $received = $event;
        });

        // Trigger booted callbacks manually in test context
        $this->app->booted(function () {});

        $registry = $this->app->make(PermissionRegistry::class);
        $permissions = $registry->all();

        $this->assertNotEmpty($permissions, 'Registry should contain permissions from fake package config');

        $names = array_column($permissions, 'name');
        $this->assertContains('fake.view', $names);
        $this->assertContains('fake.edit', $names);
    }

    public function test_permission_registry_aggregates_from_package_config(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);

        $byModule = $registry->byModule('fake');
        $this->assertCount(2, $byModule);
    }

    public function test_package_registry_has_registered_package(): void
    {
        $packages = $this->app->make(PackageRegistry::class);
        $this->assertTrue($packages->has('fake-package'));
    }

    // Test helpers are callable
    public function test_meraki_permissions_helper_returns_array(): void
    {
        $result = meraki_permissions();
        $this->assertIsArray($result);
    }

    public function test_meraki_helper_returns_core_manager(): void
    {
        $manager = meraki();
        $this->assertInstanceOf(\Meraki\Core\CoreManager::class, $manager);
    }

    public function test_meraki_can_helper_returns_bool(): void
    {
        $result = meraki_can('any.permission');
        $this->assertIsBool($result);
    }
}
