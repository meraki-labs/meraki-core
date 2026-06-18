<?php

namespace Meraki\Core\Tests;

use Meraki\Core\CoreManager;
use Meraki\Core\Adapters\LaravelAuthAdapter;
use Meraki\Core\Hooks\HookRegistry;
use Meraki\Core\Adapters\LaravelGateAdapter;
use Meraki\Core\Contracts\AuthDriver;
use Meraki\Core\Contracts\PermissionDriver;
use Meraki\Core\Exceptions\CapabilityDriverNotFoundException;
use Meraki\Core\Exceptions\MerakiException;
use Meraki\Core\Facades\Meraki;
use Meraki\Core\Testing\MerakiTestCase;

class CoreManagerTest extends MerakiTestCase
{
    // Test 1: fallback to Laravel adapters when no package driver registered
    public function test_auth_capability_returns_laravel_adapter_by_default(): void
    {
        $driver = $this->app->make(CoreManager::class)->auth();
        $this->assertInstanceOf(LaravelAuthAdapter::class, $driver);
        $this->assertInstanceOf(AuthDriver::class, $driver);
    }

    public function test_permission_capability_returns_laravel_adapter_by_default(): void
    {
        $driver = $this->app->make(CoreManager::class)->permission();
        $this->assertInstanceOf(LaravelGateAdapter::class, $driver);
        $this->assertInstanceOf(PermissionDriver::class, $driver);
    }

    public function test_can_delegates_to_permission_driver(): void
    {
        $fakeDriver = new class implements PermissionDriver {
            public function can(string $permission, mixed $user = null): bool
            {
                return $permission === 'allowed';
            }
        };

        $manager = $this->app->make(CoreManager::class);
        $manager->extend('permission', 'fake', fn ($app) => $fakeDriver);

        $this->assertTrue($manager->can('allowed'));
        $this->assertFalse($manager->can('denied'));
    }

    // Test 2: extend + auto selects last registered package driver
    public function test_auto_driver_resolves_to_last_registered_package_driver(): void
    {
        $firstDriver = new class implements AuthDriver {
            public function check(): bool { return false; }
            public function id(): mixed { return 'first'; }
            public function user(): ?object { return null; }
        };

        $lastDriver = new class implements AuthDriver {
            public function check(): bool { return true; }
            public function id(): mixed { return 'last'; }
            public function user(): ?object { return null; }
        };

        $manager = $this->app->make(CoreManager::class);
        $manager->extend('auth', 'first-package', fn ($app) => $firstDriver);
        $manager->extend('auth', 'last-package', fn ($app) => $lastDriver);

        $resolved = $manager->auth();
        $this->assertSame('last', $resolved->id());
    }

    public function test_extend_then_auto_resolves_package_driver_not_laravel(): void
    {
        $fakeDriver = new class implements AuthDriver {
            public function check(): bool { return true; }
            public function id(): mixed { return 'meraki-auth'; }
            public function user(): ?object { return null; }
        };

        $manager = $this->app->make(CoreManager::class);
        $manager->extend('auth', 'meraki-auth', fn ($app) => $fakeDriver);

        $resolved = $manager->auth();
        $this->assertNotInstanceOf(LaravelAuthAdapter::class, $resolved);
        $this->assertSame('meraki-auth', $resolved->id());
    }

    // Test 3: specific driver via config
    public function test_specific_driver_resolved_from_config(): void
    {
        $specificDriver = new class implements AuthDriver {
            public function check(): bool { return true; }
            public function id(): mixed { return 'specific'; }
            public function user(): ?object { return null; }
        };

        $manager = $this->app->make(CoreManager::class);
        $manager->extend('auth', 'meraki-auth', fn ($app) => $specificDriver);

        config(['meraki.capabilities.auth.driver' => 'meraki-auth']);

        $resolved = $manager->capability('auth');
        $this->assertSame('specific', $resolved->id());
    }

    public function test_non_existent_driver_throws_capability_driver_not_found(): void
    {
        config(['meraki.capabilities.auth.driver' => 'non-existent']);

        $manager = $this->app->make(CoreManager::class);

        $this->expectException(CapabilityDriverNotFoundException::class);
        $this->expectExceptionMessageMatches('/non-existent/');

        $manager->auth();
    }

    public function test_driver_not_found_is_meraki_exception(): void
    {
        config(['meraki.capabilities.auth.driver' => 'non-existent']);

        $manager = $this->app->make(CoreManager::class);

        $this->expectException(MerakiException::class);

        $manager->auth();
    }

    // Test: hooks() returns HookRegistry
    public function test_hooks_returns_hook_registry(): void
    {
        $manager = $this->app->make(CoreManager::class);
        $this->assertInstanceOf(HookRegistry::class, $manager->hooks());
    }

    public function test_hooks_returns_same_singleton_instance(): void
    {
        $manager = $this->app->make(CoreManager::class);
        $this->assertSame($manager->hooks(), $this->app->make(HookRegistry::class));
    }

    // Test: packages() returns PackageRegistry
    public function test_packages_returns_package_registry(): void
    {
        $manager = $this->app->make(CoreManager::class);
        $registry = $manager->packages();

        $registry->register('fake-package', ['provider' => 'FakeProvider', 'config' => 'fake']);

        $this->assertTrue($registry->has('fake-package'));
        $this->assertFalse($registry->has('other-package'));
    }

    // Test: Facade works
    public function test_meraki_facade_resolves_core_manager(): void
    {
        $this->assertInstanceOf(AuthDriver::class, Meraki::auth());
        $this->assertInstanceOf(PermissionDriver::class, Meraki::permission());
    }

    // Test: resolved driver is cached (same instance)
    public function test_resolved_driver_is_cached(): void
    {
        $manager = $this->app->make(CoreManager::class);
        $first = $manager->auth();
        $second = $manager->auth();
        $this->assertSame($first, $second);
    }

    // Test: cache invalidated after extend
    public function test_cache_invalidated_after_extend(): void
    {
        $manager = $this->app->make(CoreManager::class);
        $before = $manager->auth();
        $this->assertInstanceOf(LaravelAuthAdapter::class, $before);

        $fakeDriver = new class implements AuthDriver {
            public function check(): bool { return true; }
            public function id(): mixed { return 'new'; }
            public function user(): ?object { return null; }
        };

        $manager->extend('auth', 'new-package', fn ($app) => $fakeDriver);
        $after = $manager->auth();
        $this->assertNotSame($before, $after);
    }
}
