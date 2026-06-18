<?php

namespace Meraki\Core\Console\Commands;

use Illuminate\Console\Command;
use Meraki\Core\Installer\State\MerakiState;
use Meraki\Core\Modules\PackageRegistry;

class DoctorCommand extends Command
{
    protected $signature = 'meraki:doctor';
    protected $description = 'Check Meraki installation status';

    public function __construct(
        private readonly PackageRegistry $packages,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $state = MerakiState::load();

        $this->info('Meraki Health Check');
        $this->line(str_repeat('─', 40));
        $this->line('installed:       ' . ($state->installed ? 'YES' : 'NO'));
        $this->line('laravel version: ' . ($state->laravelVersion ?: 'unknown'));
        $this->line('installed at:    ' . ($state->installedAt ?: 'N/A'));

        $this->newLine();

        $allPackages = $this->packages->all();
        $this->info('Registered Packages (' . count($allPackages) . '):');
        if (empty($allPackages)) {
            $this->line('  (none)');
        } else {
            $rows = [];
            foreach ($allPackages as $name => $meta) {
                $rows[] = [$name, $meta['provider'] ?? '', $meta['config'] ?? ''];
            }
            $this->table(['Name', 'Provider', 'Config'], $rows);
        }

        $this->newLine();

        $capabilities = config('meraki.capabilities', []);
        $this->info('Capabilities:');
        if (empty($capabilities)) {
            $this->line('  (none configured)');
        } else {
            $rows = [];
            foreach ($capabilities as $capability => $cfg) {
                $rows[] = [$capability, $cfg['driver'] ?? 'auto'];
            }
            $this->table(['Capability', 'Driver (config)'], $rows);
        }

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
