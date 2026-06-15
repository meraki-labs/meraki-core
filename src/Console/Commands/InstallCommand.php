<?php

namespace Meraki\Core\Console\Commands;

use Illuminate\Console\Command;
use Meraki\Core\Installer\MerakiInstaller;

class InstallCommand extends Command
{
    protected $signature = 'meraki:install';
    protected $description = 'Install Meraki';

    public function handle(MerakiInstaller $installer): int
    {
        $installer->install();
        $this->info('Meraki installed successfully.');
        return self::SUCCESS;
    }
}
