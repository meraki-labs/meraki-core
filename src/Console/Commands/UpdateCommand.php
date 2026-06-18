<?php

namespace Meraki\Core\Console\Commands;

use Illuminate\Console\Command;
use Meraki\Core\Installer\MerakiInstaller;

class UpdateCommand extends Command
{
    protected $signature = 'meraki:update';
    protected $description = 'Update Meraki';

    public function handle(MerakiInstaller $installer): int
    {
        $installer->update();
        $this->info('Meraki updated successfully.');
        return self::SUCCESS;
    }
}
