<?php

namespace Meraki\Core\Console\Commands;

use Illuminate\Console\Command;
use Meraki\Core\Plugins\PluginManager;

class PluginListCommand extends Command
{
    protected $signature = 'meraki:plugin:list';
    protected $description = 'List all discovered plugins and their status';

    public function handle(PluginManager $manager): void
    {
        $plugins = $manager->all();

        if (empty($plugins)) {
            $this->info('No plugins discovered.');
            return;
        }

        $rows = [];
        foreach ($plugins as $plugin) {
            $rows[] = [
                $plugin->id(),
                $plugin->name(),
                $plugin->version(),
                $manager->isActive($plugin->id()) ? '<fg=green>active</>' : '<fg=red>inactive</>',
            ];
        }

        $this->table(['ID', 'Name', 'Version', 'Status'], $rows);
    }
}
