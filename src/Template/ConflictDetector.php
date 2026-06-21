<?php

namespace Meraki\Core\Template;

class ConflictDetector
{
    /** @return ConflictedFile[] */
    public function detect(TemplateManifest $manifest): array
    {
        $conflicts = [];

        foreach ($manifest->getManagedPaths() as $relativePath) {
            $absolutePath = base_path($relativePath);

            if (!file_exists($absolutePath)) {
                continue;
            }

            $currentChecksum = $this->checksum($absolutePath);
            $manifestChecksum = $manifest->getChecksum($relativePath);

            if ($currentChecksum !== $manifestChecksum) {
                $conflicts[] = new ConflictedFile(
                    relativePath: $relativePath,
                    manifestChecksum: $manifestChecksum,
                    currentChecksum: $currentChecksum,
                );
            }
        }

        return $conflicts;
    }

    public function checksum(string $absolutePath): string
    {
        return 'sha256:' . hash_file('sha256', $absolutePath);
    }
}
