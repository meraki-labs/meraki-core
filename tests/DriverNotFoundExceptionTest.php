<?php

namespace Meraki\Core\Tests;

use Orchestra\Testbench\TestCase;
use Meraki\Core\CoreManager;
use Meraki\Core\CoreServiceProvider;
use Meraki\Core\Exceptions\DriverNotFoundException;
use Meraki\Core\Exceptions\MerakiException;

class DriverNotFoundExceptionTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    public function test_driver_not_found_exception_is_thrown_when_driver_missing(): void
    {
        config(['meraki.capabilities.auth.driver' => 'nonexistent']);

        $this->expectException(DriverNotFoundException::class);
        $this->app->make(CoreManager::class)->capability('auth');
    }

    public function test_exception_message_contains_driver_name(): void
    {
        config(['meraki.capabilities.auth.driver' => 'nonexistent']);

        try {
            $this->app->make(CoreManager::class)->capability('auth');
            $this->fail('Expected DriverNotFoundException');
        } catch (DriverNotFoundException $e) {
            $this->assertStringContainsString('nonexistent', $e->getMessage());
        }
    }

    public function test_exception_message_contains_capability_name(): void
    {
        config(['meraki.capabilities.auth.driver' => 'nonexistent']);

        try {
            $this->app->make(CoreManager::class)->capability('auth');
            $this->fail('Expected DriverNotFoundException');
        } catch (DriverNotFoundException $e) {
            $this->assertStringContainsString('auth', $e->getMessage());
        }
    }

    public function test_exception_message_contains_available_drivers(): void
    {
        $core = $this->app->make(CoreManager::class);
        $core->extend('auth', 'custom-one', fn () => new \stdClass());
        config(['meraki.capabilities.auth.driver' => 'nonexistent']);

        try {
            $core->capability('auth');
            $this->fail('Expected DriverNotFoundException');
        } catch (DriverNotFoundException $e) {
            $this->assertStringContainsString('custom-one', $e->getMessage());
        }
    }

    public function test_exception_message_shows_none_when_no_drivers_registered(): void
    {
        config(['meraki.capabilities.auth.driver' => 'nonexistent']);

        try {
            $this->app->make(CoreManager::class)->capability('auth');
            $this->fail('Expected DriverNotFoundException');
        } catch (DriverNotFoundException $e) {
            $this->assertStringContainsString('(none registered)', $e->getMessage());
        }
    }

    public function test_driver_not_found_is_subclass_of_meraki_exception(): void
    {
        $e = DriverNotFoundException::for('auth', 'missing', []);
        $this->assertInstanceOf(MerakiException::class, $e);
        $this->assertInstanceOf(\RuntimeException::class, $e);
    }

    public function test_factory_method_builds_correct_message(): void
    {
        $e = DriverNotFoundException::for('permission', 'my-driver', ['a', 'b']);
        $this->assertStringContainsString('my-driver', $e->getMessage());
        $this->assertStringContainsString('permission', $e->getMessage());
        $this->assertStringContainsString('a, b', $e->getMessage());
    }

    public function test_meraki_info_command_returns_success(): void
    {
        $this->artisan('meraki:info')->assertExitCode(0);
    }
}
