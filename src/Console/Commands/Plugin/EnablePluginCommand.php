<?php

namespace Meraki\Core\Console\Commands\Plugin;

use Illuminate\Console\Command;
use Meraki\Core\Plugin\PluginLoader;

class EnablePluginCommand extends Command
{
    protected $signature = 'meraki:plugin:enable {name : Plugin name}';
    protected $description = 'Enable a plugin';

    public function handle(PluginLoader $loader): int
    {
        $name = $this->argument('name');

        try {
            $loader->enable($name);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Plugin [{$name}] enabled.");

        return self::SUCCESS;
    }
}
