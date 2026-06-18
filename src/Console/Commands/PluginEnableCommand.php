<?php

namespace Meraki\Core\Console\Commands;

use Illuminate\Console\Command;
use Meraki\Core\Plugins\PluginManager;
use RuntimeException;

class PluginEnableCommand extends Command
{
    protected $signature = 'meraki:plugin:enable {id : Plugin slug}';
    protected $description = 'Enable a plugin (takes effect on next request)';

    public function handle(PluginManager $manager): int
    {
        $id = $this->argument('id');

        try {
            $manager->activate($id);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Plugin [{$id}] activated. Note: changes take effect on the next request.");

        return self::SUCCESS;
    }
}
