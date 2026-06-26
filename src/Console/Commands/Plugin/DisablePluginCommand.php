<?php

namespace Meraki\Core\Console\Commands\Plugin;

use Illuminate\Console\Command;
use Meraki\Core\Plugin\PluginLoader;

class DisablePluginCommand extends Command
{
    protected $signature = 'meraki:plugin:disable {name : Plugin name}';
    protected $description = 'Disable a plugin';

    public function handle(PluginLoader $loader): int
    {
        $name = $this->argument('name');

        try {
            $loader->disable($name);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Plugin [{$name}] disabled.");

        return self::SUCCESS;
    }
}
