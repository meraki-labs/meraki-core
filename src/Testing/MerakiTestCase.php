<?php

namespace Meraki\Core\Testing;

use Meraki\Core\CoreServiceProvider;
use Meraki\Core\Facades\Meraki;
use Orchestra\Testbench\TestCase;

abstract class MerakiTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Meraki' => Meraki::class];
    }
}
