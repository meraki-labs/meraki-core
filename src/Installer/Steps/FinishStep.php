<?php

namespace Meraki\Core\Installer\Steps;

use Meraki\Core\Installer\InstallerContext;

class FinishStep implements Step
{
    public function run(InstallerContext $context): void
    {
        // Hook meraki.installed / meraki.updated được fire bởi MerakiInstaller::run()
        // sau khi bước này hoàn tất — xem HookRegistry.
    }
}
