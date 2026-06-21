<?php

namespace Meraki\Core\Console\Commands;

use Illuminate\Console\Command;
use Meraki\Core\Installer\MerakiInstaller;

class UpdateCommand extends Command
{
    protected $signature = 'meraki:update';
    protected $description = 'Update Meraki packages and managed files';

    public function handle(MerakiInstaller $installer): int
    {
        $this->info('Updating Meraki...');

        $context = $installer->update();

        $conflicts = $context->get('update_conflicts', []);
        $backupDir = $context->get('update_backup_dir');

        if (!empty($conflicts)) {
            $this->newLine();
            $this->warn('Your changes to the following managed files have been backed up:');
            foreach ($conflicts as $path) {
                $this->line("    <fg=yellow>!</> {$path}");
            }
            if ($backupDir) {
                $this->line('  → ' . ltrim(str_replace(base_path(), '', $backupDir), DIRECTORY_SEPARATOR));
            }
        }

        $fileCount = $context->get('manifest_file_count', 0);

        $this->newLine();
        $this->info("Meraki updated. Manifest tracking {$fileCount} managed file(s).");

        return self::SUCCESS;
    }
}
