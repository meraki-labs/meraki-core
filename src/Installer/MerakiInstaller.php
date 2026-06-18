<?php

namespace Meraki\Core\Installer;

use Meraki\Core\Installer\Steps\DetectLaravelVersionStep;
use Meraki\Core\Installer\Steps\PublishConfigStep;
use Meraki\Core\Installer\Steps\PublishMigrationsStep;
use Meraki\Core\Installer\Steps\WriteStateStep;
use Meraki\Core\Installer\Steps\FinishStep;

class MerakiInstaller
{
    public function install(): void
    {
        $this->run('install');
    }

    public function update(): void
    {
        $this->run('update');
    }

    protected function run(string $mode): void
    {
        $context = new InstallerContext();
        $context->mode = $mode;

        $steps = [
            DetectLaravelVersionStep::class,
            PublishConfigStep::class,
            PublishMigrationsStep::class,
            WriteStateStep::class,
            FinishStep::class,
        ];

        foreach ($steps as $stepClass) {
            app($stepClass)->run($context);
        }
    }
}
