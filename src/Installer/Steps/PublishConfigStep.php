<?php

namespace Meraki\Core\Installer\Steps;

use Illuminate\Support\Facades\Artisan;
use Meraki\Core\Installer\InstallerContext;

class PublishConfigStep implements Step
{
    public function run(InstallerContext $context): void
    {
        Artisan::call('vendor:publish', [
            '--tag' => 'meraki-config',
            '--force' => false,
        ]);
    }
}
