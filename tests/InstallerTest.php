<?php

namespace Meraki\Core\Tests;

use Orchestra\Testbench\TestCase;
use Meraki\Core\CoreServiceProvider;
use Meraki\Core\Installer\MerakiInstaller;
use Illuminate\Support\Facades\Artisan;

class InstallerTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    public function test_meraki_installer_is_bound_in_container(): void
    {
        $installer = $this->app->make(MerakiInstaller::class);
        $this->assertInstanceOf(MerakiInstaller::class, $installer);
    }

    public function test_meraki_install_command_is_registered(): void
    {
        $commands = array_keys(Artisan::all());
        $this->assertContains('meraki:install', $commands);
    }

    public function test_meraki_update_command_is_registered(): void
    {
        $commands = array_keys(Artisan::all());
        $this->assertContains('meraki:update', $commands);
    }

    public function test_meraki_doctor_command_is_registered(): void
    {
        $commands = array_keys(Artisan::all());
        $this->assertContains('meraki:doctor', $commands);
    }
}
