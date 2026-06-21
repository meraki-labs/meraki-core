<?php

namespace Meraki\Core\Console\Commands;

use Illuminate\Console\Command;
use Meraki\Core\Installer\MerakiInstaller;

class InstallCommand extends Command
{
    protected $signature = 'meraki:install';
    protected $description = 'Install Meraki and build the managed-file manifest';

    public function handle(MerakiInstaller $installer): int
    {
        $this->info('Installing Meraki...');

        $context = $installer->install();

        $fileCount = $context->get('manifest_file_count', 0);

        $this->newLine();
        $this->info("Meraki installed. Manifest tracking {$fileCount} managed file(s).");

        return self::SUCCESS;
    }
}
