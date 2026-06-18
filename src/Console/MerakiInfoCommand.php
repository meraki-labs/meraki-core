<?php

namespace Meraki\Core\Console;

use Illuminate\Console\Command;
use Meraki\Core\CoreManager;
use Meraki\Core\Modules\PermissionRegistry;

class MerakiInfoCommand extends Command
{
    protected $signature   = 'meraki:info';
    protected $description = 'Display Meraki Core gate state (drivers, packages, permissions)';

    public function handle(CoreManager $core, PermissionRegistry $permissions): int
    {
        $this->line('');
        $this->line('  <options=bold>Meraki Core — Driver State</>');
        $this->line('  ' . str_repeat('─', 40));

        $capabilities = array_keys(config('meraki.capabilities', []));
        $rows = [];

        foreach ($capabilities as $cap) {
            $configuredDriver = config("meraki.capabilities.{$cap}.driver", 'auto');
            $resolvedClass = '(not resolved)';

            try {
                $core->capability($cap);
                $resolved = $core->resolvedCapabilities();
                $resolvedClass = $resolved[$cap] ?? '(not resolved)';
            } catch (\Throwable) {
                $resolvedClass = '(error)';
            }

            $registeredNames = $core->registeredDriverNames()[$cap] ?? [];
            $resolvedName = empty($registeredNames)
                ? 'laravel'
                : end($registeredNames);

            $driverDisplay = $configuredDriver === 'auto'
                ? "auto → {$resolvedName}"
                : $configuredDriver;

            $rows[] = [$cap, $driverDisplay, $resolvedClass];
        }

        $this->table(['Capability', 'Driver', 'Class'], $rows);

        $this->line('');
        $this->line('  <options=bold>Registered Packages (PackageRegistry)</>');
        $this->line('  ' . str_repeat('─', 40));

        $packages = $core->packages()->all();
        if (empty($packages)) {
            $this->line('  (none)');
        } else {
            foreach ($packages as $name => $meta) {
                $provider = $meta['provider'] ?? '(unknown)';
                $this->line("  {$name} (provider: {$provider})");
            }
        }

        $this->line('');
        $permCount = count($permissions->all());
        $this->line("  Permission Registry: {$permCount} permissions loaded");
        $this->line('');

        return self::SUCCESS;
    }
}
