<?php

namespace Meraki\Core\Installer\Steps;

use Meraki\Core\Installer\InstallerContext;

interface Step
{
    public function run(InstallerContext $context): void;
}
