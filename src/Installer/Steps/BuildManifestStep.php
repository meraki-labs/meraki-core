<?php

namespace Meraki\Core\Installer\Steps;

use Meraki\Core\Installer\InstallerContext;
use Meraki\Core\Template\ConflictDetector;
use Meraki\Core\Template\TemplateManifest;

class BuildManifestStep implements Step
{
    public function run(InstallerContext $context): void
    {
        $manifest = TemplateManifest::load();
        $detector = new ConflictDetector();

        foreach ($this->discoverManagedFiles() as $absolutePath) {
            if (!file_exists($absolutePath)) {
                continue;
            }

            $relativePath = ltrim(
                str_replace(base_path(), '', $absolutePath),
                DIRECTORY_SEPARATOR
            );

            $manifest->record($relativePath, $detector->checksum($absolutePath));
        }

        $manifest->save();

        $context->set('manifest_file_count', count($manifest->getManagedPaths()));
    }

    private function discoverManagedFiles(): array
    {
        return glob(config_path('meraki*.php')) ?: [];
    }
}
