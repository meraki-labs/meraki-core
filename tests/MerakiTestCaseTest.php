<?php

namespace Meraki\Core\Tests;

use Meraki\Core\CoreManager;
use Meraki\Core\Testing\FakePackageServiceProvider;
use Meraki\Core\Testing\MerakiTestCase;

class MerakiTestCaseTest extends MerakiTestCase
{
    public function test_core_manager_is_available(): void
    {
        $this->assertInstanceOf(CoreManager::class, $this->app->make(CoreManager::class));
    }

    public function test_fake_package_service_provider_registers_package(): void
    {
        $provider = new FakePackageServiceProvider($this->app);
        $provider->register();

        $packages = $this->app->make(CoreManager::class)->packages();
        $this->assertTrue($packages->has('fake-package'));
    }
}
