<?php

namespace Meraki\Core\Installer\Steps;

use Meraki\Core\Installer\InstallerContext;

class DetectLaravelVersionStep implements Step
{
    public function run(InstallerContext $context): void
    {
        $context->laravelVersion = app()->version();
    }
}
