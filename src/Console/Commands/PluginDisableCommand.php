<?php

namespace Meraki\Core\Console\Commands;

use Illuminate\Console\Command;
use Meraki\Core\Plugins\PluginManager;
use RuntimeException;

class PluginDisableCommand extends Command
{
    protected $signature = 'meraki:plugin:disable {id : Plugin slug}';
    protected $description = 'Disable a plugin';

    public function handle(PluginManager $manager): int
    {
        $id = $this->argument('id');

        try {
            $manager->deactivate($id);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Plugin [{$id}] deactivated.");

        return self::SUCCESS;
    }
}
