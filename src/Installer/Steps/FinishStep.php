<?php

namespace Meraki\Core\Installer\Steps;

use Meraki\Core\Installer\InstallerContext;

class FinishStep implements Step
{
    public function run(InstallerContext $context): void
    {
        // Reserved for post-install / post-update hooks
    }
}
