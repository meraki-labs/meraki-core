<?php

namespace Meraki\Core\Testing;

use Meraki\Core\CoreManager;

trait InteractsWithMerakiCore
{
    protected function registerFakeDriver(string $capability, string $name, object $driver): void
    {
        $this->app->make(CoreManager::class)->extend(
            $capability, $name, fn () => $driver
        );
        config(["meraki.capabilities.{$capability}.driver" => $name]);
    }

    protected function assertMerakiCan(string $permission, mixed $user = null): void
    {
        $this->assertTrue(
            $this->app->make(CoreManager::class)->can($permission, $user),
            "Failed asserting that Meraki can [{$permission}]."
        );
    }

    protected function assertMerakiCannot(string $permission, mixed $user = null): void
    {
        $this->assertFalse(
            $this->app->make(CoreManager::class)->can($permission, $user),
            "Failed asserting that Meraki cannot [{$permission}]."
        );
    }
}
