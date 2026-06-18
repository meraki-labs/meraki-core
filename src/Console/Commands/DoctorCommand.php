<?php

namespace Meraki\Core\Console\Commands;

use Illuminate\Console\Command;
use Meraki\Core\Installer\State\MerakiState;
use Meraki\Core\Modules\PackageRegistry;

class DoctorCommand extends Command
{
    protected $signature = 'meraki:doctor';
    protected $description = 'Check Meraki installation status';

    public function handle(): int
    {
        $state = MerakiState::load();

        $this->line('Meraki installed: ' . ($state->installed ? 'YES' : 'NO'));
        $this->line('Laravel version: ' . ($state->laravelVersion ?: 'unknown'));
        $this->line('Installed at: ' . ($state->installedAt ?: 'N/A'));

        $this->newLine();
        $this->renderDependencyGraph();

        return self::SUCCESS;
    }

    protected function renderDependencyGraph(): void
    {
        $this->line('Dependency Graph');

        /** @var PackageRegistry $packages */
        $packages = app(PackageRegistry::class);
        $all      = $packages->all();

        if (empty($all)) {
            $this->line('  (no packages registered)');
            return;
        }

        $registeredNames = array_keys($all);

        foreach ($all as $name => $meta) {
            $requires = $meta['requires'] ?? [];

            if (empty($requires)) {
                $this->line("  {$name}  \u{2713} registered  (no deps)");
                continue;
            }

            $depStatuses = [];
            foreach ($requires as $dep) {
                $status        = in_array($dep, $registeredNames, true) ? "\u{2713}" : "\u{2717} missing";
                $depStatuses[] = "{$dep} {$status}";
            }

            $this->line("  {$name}  \u{2713} registered  requires: " . implode(', ', $depStatuses));
        }
    }
}
