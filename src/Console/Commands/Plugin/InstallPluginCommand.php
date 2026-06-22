<?php

namespace Meraki\Core\Console\Commands\Plugin;

use Illuminate\Console\Command;
use Meraki\Core\Plugin\PluginLoader;

class InstallPluginCommand extends Command
{
    protected $signature = 'meraki:plugin:install {name : Plugin name}';
    protected $description = 'Run the install hook for a plugin';

    public function handle(PluginLoader $loader): int
    {
        $name = $this->argument('name');

        try {
            $loader->install($name);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Plugin [{$name}] installed.");

        return self::SUCCESS;
    }
}
