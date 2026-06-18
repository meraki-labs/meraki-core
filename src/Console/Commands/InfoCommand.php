<?php

namespace Meraki\Core\Console\Commands;

use Illuminate\Console\Command;
use Meraki\Core\CoreManager;
use Meraki\Core\Installer\State\MerakiState;
use Meraki\Core\Modules\PackageRegistry;
use Meraki\Core\Modules\PermissionRegistry;

class InfoCommand extends Command
{
    protected $signature = 'meraki:info';
    protected $description = 'Display Meraki installation and runtime information';

    public function __construct(
        private readonly CoreManager $manager,
        private readonly PackageRegistry $packages,
        private readonly PermissionRegistry $permissions,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $state = MerakiState::load();

        $this->info('Meraki System Information');
        $this->newLine();

        // General section
        $this->line('  <fg=yellow>General</>');
        $this->components->twoColumnDetail('  Version', 'dev-main');
        $this->components->twoColumnDetail('  Status', $state->installed ? '<fg=green>Installed ✓</>' : '<fg=red>Not Installed</>');
        $this->components->twoColumnDetail('  Installed At', $state->installedAt ?: 'N/A');
        $this->components->twoColumnDetail('  Laravel Version', $state->laravelVersion ?: 'unknown');
        $this->newLine();

        // Active Drivers section
        $this->line('  <fg=yellow>Active Drivers</>');
        $authDriver = $this->resolveDriverName('auth');
        $permDriver = $this->resolveDriverName('permission');
        $authConfig = config('meraki.capabilities.auth.driver', 'auto');
        $permConfig = config('meraki.capabilities.permission.driver', 'auto');
        $this->components->twoColumnDetail('  Auth', "{$authDriver} ({$authConfig})");
        $this->components->twoColumnDetail('  Permission', "{$permDriver} ({$permConfig})");
        $this->newLine();

        // Registered Packages section
        $allPackages = $this->packages->all();
        $packageCount = count($allPackages);
        $this->line('  <fg=yellow>Registered Packages</> <fg=gray>(' . $packageCount . ')</>');
        if ($packageCount === 0) {
            $this->line('  <fg=gray>  None</>');
        } else {
            foreach ($allPackages as $name => $meta) {
                $configKey = $meta['config'] ?? 'N/A';
                $this->line("  <fg=cyan>  {$name}</> <fg=gray>config: {$configKey}</>");
            }
        }
        $this->newLine();

        // Permissions section
        $allPermissions = $this->permissions->all();
        $permCount = count($allPermissions);
        $this->line('  <fg=yellow>Permissions</> <fg=gray>(' . $permCount . ' total)</>');
        if ($permCount === 0) {
            $this->line('  <fg=gray>  None</>');
        } else {
            $byModule = [];
            foreach ($allPermissions as $perm) {
                $module = $perm['module'] ?? 'default';
                $byModule[$module] = ($byModule[$module] ?? 0) + 1;
            }
            $parts = [];
            foreach ($byModule as $module => $count) {
                $parts[] = "{$module} ({$count})";
            }
            $this->line('  ' . implode('  ', $parts));
        }
        $this->newLine();

        return self::SUCCESS;
    }

    private function resolveDriverName(string $capability): string
    {
        try {
            $driver = $capability === 'auth'
                ? $this->manager->auth()
                : $this->manager->permission();
            $class = get_class($driver);
            return class_basename($class);
        } catch (\InvalidArgumentException $e) {
            return 'Driver not registered: ' . config("meraki.capabilities.{$capability}.driver", 'auto');
        }
    }
}
