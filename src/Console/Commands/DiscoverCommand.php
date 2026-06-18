<?php

namespace Meraki\Core\Console\Commands;

use Illuminate\Console\Command;
use Meraki\Core\Modules\PluginDiscovery;

class DiscoverCommand extends Command
{
    protected $signature = 'meraki:discover {--refresh : Clear cache and re-scan}';
    protected $description = 'Discover and list all installed Meraki plugins';

    public function handle(PluginDiscovery $discovery): int
    {
        if ($this->option('refresh')) {
            $discovery->clearCache();
            $this->line('Plugin cache cleared.');
        }

        $manifests = $discovery->discover();

        if (empty($manifests)) {
            $this->info('No Meraki plugins discovered.');
            return self::SUCCESS;
        }

        $rows = array_map(fn($m) => [
            $m->id,
            $m->name,
            $m->version ?? '-',
            $m->provider,
            $m->config,
            $m->source,
        ], $manifests);

        $this->table(
            ['ID', 'Name', 'Version', 'Provider', 'Config', 'Source'],
            $rows
        );

        $this->info(count($manifests) . ' plugin(s) discovered.');

        return self::SUCCESS;
    }
}
