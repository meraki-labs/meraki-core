<?php

namespace Meraki\Core\Testing;

use Illuminate\Support\ServiceProvider;
use Meraki\Core\CoreManager;

class FakePackageServiceProvider extends ServiceProvider
{
    public string $packageName = 'fake-package';
    public string $configKey = 'fake-package';

    public function register(): void
    {
        $core = $this->app->make(CoreManager::class);
        $core->packages()->register($this->packageName, [
            'provider' => static::class,
            'config'   => $this->configKey,
        ]);
    }
}
