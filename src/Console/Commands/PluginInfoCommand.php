<?php

namespace Meraki\Core\Console\Commands;

use Illuminate\Console\Command;
use Meraki\Core\Plugins\PluginManager;
use Meraki\Core\Plugins\PluginRepository;

class PluginInfoCommand extends Command
{
    protected $signature = 'meraki:plugin:info {id : Plugin slug}';
    protected $description = 'Display detailed information about a plugin';

    public function handle(PluginManager $manager, PluginRepository $repo): int
    {
        $id = $this->argument('id');
        $plugin = $manager->find($id);

        if (!$plugin) {
            $this->error("Plugin [{$id}] not found.");
            return self::FAILURE;
        }

        $records = collect($repo->all());
        $record  = $records->firstWhere('id', $id);

        $this->table(['Field', 'Value'], [
            ['ID',          $plugin->id()],
            ['Name',        $plugin->name()],
            ['Version',     $plugin->version()],
            ['Description', $plugin->description() ?: '(none)'],
            ['Status',      $manager->isEnabled($id) ? 'enabled' : 'disabled'],
            ['Enabled at',  $record?->enabled_at ?? '—'],
        ]);

        return self::SUCCESS;
    }
}
