<?php

namespace Meraki\Core\Installer\Steps;

use Illuminate\Support\Facades\Artisan;
use Meraki\Core\Installer\InstallerContext;

class PublishMigrationsStep implements Step
{
    public function run(InstallerContext $context): void
    {
        Artisan::call('vendor:publish', [
            '--tag' => 'meraki-migrations',
            '--force' => false,
        ]);
    }
}
