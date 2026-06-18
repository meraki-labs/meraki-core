<?php

namespace Meraki\Core\Tests;

use Orchestra\Testbench\TestCase;
use Meraki\Core\CoreServiceProvider;
use Meraki\Core\Modules\PackageRegistry;
use Meraki\Core\Exceptions\CircularDependencyException;
use Meraki\Core\Exceptions\MissingDependencyException;
use Illuminate\Support\ServiceProvider;

class DependencyIntegrationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    public function test_boot_succeeds_with_no_packages(): void
    {
        // App boots normally with zero registered packages — no exception
        $packages = $this->app->make(PackageRegistry::class);
        $this->assertEmpty($packages->all());
    }

    public function test_boot_succeeds_with_valid_dependencies(): void
    {
        $packages = $this->app->make(PackageRegistry::class);
        $packages->register('meraki-hub', []);
        $packages->register('meraki-cms', ['requires' => ['meraki-hub']]);

        // Manually re-trigger validation (boot already ran, so we call protected method via reflection)
        $provider = new CoreServiceProvider($this->app);
        $method   = new \ReflectionMethod($provider, 'validateDependencies');
        $method->setAccessible(true);

        $this->expectNotToPerformAssertions();
        $method->invoke($provider); // must not throw
    }

    public function test_boot_throws_missing_dependency_exception(): void
    {
        $packages = $this->app->make(PackageRegistry::class);
        $packages->register('meraki-cms', ['requires' => ['meraki-hub']]);
        // meraki-hub is NOT registered

        $provider = new CoreServiceProvider($this->app);
        $method   = new \ReflectionMethod($provider, 'validateDependencies');
        $method->setAccessible(true);

        $this->expectException(MissingDependencyException::class);
        $method->invoke($provider);
    }

    public function test_boot_throws_circular_dependency_exception(): void
    {
        $packages = $this->app->make(PackageRegistry::class);
        $packages->register('pkg-a', ['requires' => ['pkg-b']]);
        $packages->register('pkg-b', ['requires' => ['pkg-a']]);

        $provider = new CoreServiceProvider($this->app);
        $method   = new \ReflectionMethod($provider, 'validateDependencies');
        $method->setAccessible(true);

        $this->expectException(CircularDependencyException::class);
        $method->invoke($provider);
    }
}
