<?php

namespace Meraki\Core\Console\Commands;

use Illuminate\Console\Command;
use Meraki\Core\Installer\State\MerakiState;

class DoctorCommand extends Command
{
    protected $signature = 'meraki:doctor';
    protected $description = 'Check Meraki installation status';

    public function handle(): int
    {
        $state = MerakiState::load();

        $this->line('Meraki installed: ' . ($state->installed ? 'YES' : 'NO'));
        $this->line('Laravel version: ' . ($state->laravelVersion ?: 'unknown'));
        $this->line('Installed at: ' . ($state->installedAt ?: 'N/A'));

        return self::SUCCESS;
    }
}
