<?php

namespace Meraki\Core\Console\Commands\Plugin;

use Illuminate\Console\Command;
use Meraki\Core\Plugin\PluginLoader;

class ListPluginsCommand extends Command
{
    protected $signature = 'meraki:plugin:list';
    protected $description = 'List all discovered plugins and their status';

    public function handle(PluginLoader $loader): void
    {
        $discovered = $loader->discovered();

        if (empty($discovered)) {
            $this->info('No plugins discovered.');
            return;
        }

        $rows = [];
        foreach ($discovered as $name => $class) {
            $rows[] = [
                $name,
                $class,
                $loader->isEnabled($name) ? '<fg=green>enabled</>' : '<fg=red>disabled</>',
                $loader->isInstalled($name) ? '<fg=green>yes</>' : '<fg=yellow>no</>',
            ];
        }

        $this->table(['Name', 'Class', 'Enabled', 'Installed'], $rows);
    }
}
