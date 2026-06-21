<?php

namespace Meraki\Core\Installer\Steps;

use Meraki\Core\Installer\InstallerContext;

class WriteStateStep implements Step
{
    public function run(InstallerContext $context): void
    {
        $data = [
            'status' => 'installed',
            'laravel_version' => $context->laravelVersion,
            'installed_at' => now()->toDateTimeString(),
        ];

        // Legacy file at project root (backward compat)
        file_put_contents(
            config('meraki.state_file'),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // Canonical location inside .meraki/
        $merakiDir = base_path('.meraki');
        if (!is_dir($merakiDir)) {
            mkdir($merakiDir, 0755, true);
        }

        file_put_contents(
            $merakiDir . '/state.json',
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
