<?php

namespace Meraki\Core\Installer;

use Meraki\Core\Hooks\HookRegistry;
use Meraki\Core\Installer\Steps\BuildManifestStep;
use Meraki\Core\Installer\Steps\CreateAdminStep;
use Meraki\Core\Installer\Steps\DetectLaravelVersionStep;
use Meraki\Core\Installer\Steps\DiscoverPluginsStep;
use Meraki\Core\Installer\Steps\FinishStep;
use Meraki\Core\Installer\Steps\MigrateStep;
use Meraki\Core\Installer\Steps\PublishConfigStep;
use Meraki\Core\Installer\Steps\PublishMigrationsStep;
use Meraki\Core\Installer\Steps\UpdateManagedFilesStep;
use Meraki\Core\Installer\Steps\WriteStateStep;

class MerakiInstaller
{
    public function install(): InstallerContext
    {
        return $this->run('install');
    }

    public function update(): InstallerContext
    {
        return $this->run('update');
    }

    protected function run(string $mode): InstallerContext
    {
        $context = new InstallerContext();
        $context->mode = $mode;

        /** @var HookRegistry $hooks */
        $hooks = app(HookRegistry::class);

        $hookNames = [
            'install' => ['meraki.installing', 'meraki.installed'],
            'update'  => ['meraki.updating', 'meraki.updated'],
        ];

        [$beforeHook, $afterHook] = $hookNames[$mode];

        $hooks->fire($beforeHook, $context);

        $steps = $mode === 'update'
            ? $this->updateSteps()
            : $this->installSteps();

        foreach ($steps as $stepClass) {
            app($stepClass)->run($context);
        }

        $hooks->fire($afterHook, $context);

        return $context;
    }

    private function installSteps(): array
    {
        return [
            DetectLaravelVersionStep::class,
            PublishConfigStep::class,
            PublishMigrationsStep::class,
            MigrateStep::class,
            WriteStateStep::class,
            DiscoverPluginsStep::class,
            BuildManifestStep::class,
            CreateAdminStep::class,
            FinishStep::class,
        ];
    }

    private function updateSteps(): array
    {
        return [
            DetectLaravelVersionStep::class,
            UpdateManagedFilesStep::class,
            PublishConfigStep::class,
            PublishMigrationsStep::class,
            WriteStateStep::class,
            DiscoverPluginsStep::class,
            BuildManifestStep::class,
            FinishStep::class,
        ];
    }
}
