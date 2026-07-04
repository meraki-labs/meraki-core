<?php

namespace Meraki\Core\Installer\Steps;

use Meraki\Core\Installer\InstallerContext;
use Illuminate\Support\Facades\Artisan;

final class MigrateStep implements Step
{
    public function run(InstallerContext $context): void
    {
        Artisan::call('migrate', ['--force' => true]);
        $context->set('migration_output', Artisan::output());
    }
}
