<?php

namespace Meraki\Core\Tests;

use Orchestra\Testbench\TestCase;
use Meraki\Core\CoreServiceProvider;
use Meraki\Core\Hooks\HookRegistry;
use Meraki\Core\Installer\InstallerContext;
use Meraki\Core\Installer\MerakiInstaller;

class InstallerHookTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    public function test_install_fires_installing_before_pipeline(): void
    {
        $order = [];

        $this->app->make(HookRegistry::class)->add('meraki.installing', function (InstallerContext $ctx) use (&$order) {
            $order[] = 'installing';
        });

        $this->app->make(MerakiInstaller::class)->install();

        $this->assertSame('installing', $order[0]);
    }

    public function test_install_fires_installed_after_pipeline(): void
    {
        $order = [];

        $this->app->make(HookRegistry::class)->add('meraki.installing', function () use (&$order) {
            $order[] = 'installing';
        });

        $this->app->make(HookRegistry::class)->add('meraki.installed', function () use (&$order) {
            $order[] = 'installed';
        });

        $this->app->make(MerakiInstaller::class)->install();

        $this->assertSame(['installing', 'installed'], $order);
    }

    public function test_update_fires_updating_and_updated(): void
    {
        $order = [];

        $this->app->make(HookRegistry::class)->add('meraki.updating', function () use (&$order) {
            $order[] = 'updating';
        });

        $this->app->make(HookRegistry::class)->add('meraki.updated', function () use (&$order) {
            $order[] = 'updated';
        });

        $this->app->make(MerakiInstaller::class)->update();

        $this->assertSame(['updating', 'updated'], $order);
    }

    public function test_hook_receives_installer_context(): void
    {
        $receivedContext = null;

        $this->app->make(HookRegistry::class)->add('meraki.installed', function (InstallerContext $ctx) use (&$receivedContext) {
            $receivedContext = $ctx;
        });

        $this->app->make(MerakiInstaller::class)->install();

        $this->assertInstanceOf(InstallerContext::class, $receivedContext);
        $this->assertSame('install', $receivedContext->mode);
    }

    public function test_hook_registered_via_container_is_called(): void
    {
        $called = false;

        app(HookRegistry::class)->add('meraki.installed', function () use (&$called) {
            $called = true;
        });

        $this->app->make(MerakiInstaller::class)->install();

        $this->assertTrue($called);
    }

    public function test_install_fires_installing_before_installed(): void
    {
        $order = [];

        $hooks = $this->app->make(HookRegistry::class);

        $hooks->add('meraki.installed', function () use (&$order) {
            $order[] = 'installed';
        });

        $hooks->add('meraki.installing', function () use (&$order) {
            $order[] = 'installing';
        });

        $this->app->make(MerakiInstaller::class)->install();

        $installingPos = array_search('installing', $order);
        $installedPos = array_search('installed', $order);

        $this->assertLessThan($installedPos, $installingPos);
    }
}
