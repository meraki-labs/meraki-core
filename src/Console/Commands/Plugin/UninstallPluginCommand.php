<?php

namespace Meraki\Core\Console\Commands\Plugin;

use Illuminate\Console\Command;
use Meraki\Core\Plugin\PluginLoader;

class UninstallPluginCommand extends Command
{
    protected $signature = 'meraki:plugin:uninstall {name : Plugin name}';
    protected $description = 'Run the uninstall hook for a plugin';

    public function handle(PluginLoader $loader): int
    {
        $name = $this->argument('name');

        if (!$this->confirm("Are you sure you want to uninstall plugin [{$name}]?")) {
            $this->info('Uninstall cancelled.');
            return self::SUCCESS;
        }

        try {
            $loader->uninstall($name);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Plugin [{$name}] uninstalled.");

        return self::SUCCESS;
    }
}
