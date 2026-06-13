<?php

namespace Meraki\Core\Tests;

use Orchestra\Testbench\TestCase;
use Meraki\Core\CoreManager;
use Meraki\Core\CoreServiceProvider;
use Meraki\Core\Contracts\AuthDriver;
use Meraki\Core\Contracts\PermissionDriver;
use Meraki\Core\Facades\Meraki;
use Meraki\Core\Testing\InteractsWithMerakiCore;

class InteractsWithMerakiCoreTest extends TestCase
{
    use InteractsWithMerakiCore;

    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Meraki' => Meraki::class];
    }

    public function test_register_fake_auth_driver_resolves_via_facade(): void
    {
        $fakeDriver = new class implements AuthDriver {
            public function user(): ?object { return null; }
            public function check(): bool { return true; }
            public function id(): mixed { return 1; }
        };

        $this->registerFakeDriver('auth', 'fake', $fakeDriver);

        $this->assertSame($fakeDriver, Meraki::auth());
    }

    public function test_assert_meraki_can_passes_when_permission_granted(): void
    {
        $fakeDriver = new class implements PermissionDriver {
            public function can(string $permission, mixed $user = null): bool
            {
                return $permission === 'allowed';
            }
        };

        $this->registerFakeDriver('permission', 'fake', $fakeDriver);

        $this->assertMerakiCan('allowed');
    }

    public function test_assert_meraki_cannot_passes_when_permission_denied(): void
    {
        $fakeDriver = new class implements PermissionDriver {
            public function can(string $permission, mixed $user = null): bool
            {
                return false;
            }
        };

        $this->registerFakeDriver('permission', 'fake', $fakeDriver);

        $this->assertMerakiCannot('anything');
    }
}
