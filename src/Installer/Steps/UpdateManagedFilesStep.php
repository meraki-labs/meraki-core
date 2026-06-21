<?php

namespace Meraki\Core\Installer\Steps;

use Meraki\Core\Installer\InstallerContext;
use Meraki\Core\Template\ConflictDetector;
use Meraki\Core\Template\TemplateManifest;

class UpdateManagedFilesStep implements Step
{
    public function run(InstallerContext $context): void
    {
        if ($context->mode !== 'update') {
            return;
        }

        $manifest = TemplateManifest::load();

        if ($manifest->isEmpty()) {
            $context->set('update_conflicts', []);
            return;
        }

        $detector = new ConflictDetector();
        $conflicts = $detector->detect($manifest);

        if (empty($conflicts)) {
            $context->set('update_conflicts', []);
            return;
        }

        $backupDir = $this->createBackupDir();

        foreach ($conflicts as $conflict) {
            $src = base_path($conflict->relativePath);
            $dest = $backupDir . DIRECTORY_SEPARATOR . basename($conflict->relativePath);
            copy($src, $dest);
        }

        $context->set('update_conflicts', array_map(fn($c) => $c->relativePath, $conflicts));
        $context->set('update_backup_dir', $backupDir);
    }

    private function createBackupDir(): string
    {
        $dir = base_path('.meraki/backups/' . now()->format('Y-m-d_H-i'));

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }
}
