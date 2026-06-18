<?php

namespace Meraki\Core\Installer\Steps;

use Meraki\Core\Installer\InstallerContext;

class WriteStateStep implements Step
{
    public function run(InstallerContext $context): void
    {
        $file = config('meraki.state_file');

        file_put_contents(
            $file,
            json_encode([
                'status' => 'installed',
                'laravel_version' => $context->laravelVersion,
                'installed_at' => now()->toDateTimeString(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
