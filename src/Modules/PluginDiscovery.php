<?php

namespace Meraki\Core\Modules;

use Meraki\Core\Exceptions\DuplicatePluginIdException;

class PluginDiscovery
{
    private ?array $cached = null;

    public function __construct(
        private readonly string $basePath,
        private readonly string $cachePath,
    ) {}

    /** @return PluginManifest[] */
    public function discover(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        if (file_exists($this->cachePath)) {
            $data = require $this->cachePath;
            $this->cached = array_map(
                fn(array $item) => PluginManifest::fromArray($item, $item['basePath'], $item['source']),
                $data
            );
            return $this->cached;
        }

        $manifests = $this->loadManifests();
        $this->writeCache($manifests);
        $this->cached = $manifests;

        return $this->cached;
    }

    public function clearCache(): void
    {
        $this->cached = null;
        if (file_exists($this->cachePath)) {
            unlink($this->cachePath);
        }
    }

    private function loadManifests(): array
    {
        $byId = [];

        foreach ($this->discoverFromComposer() as $manifest) {
            if (isset($byId[$manifest->id])) {
                throw new DuplicatePluginIdException($manifest->id);
            }
            $byId[$manifest->id] = $manifest;
        }

        foreach ($this->discoverFromPluginsDir() as $manifest) {
            if (isset($byId[$manifest->id])) {
                throw new DuplicatePluginIdException($manifest->id);
            }
            $byId[$manifest->id] = $manifest;
        }

        return array_values($byId);
    }

    /** @return PluginManifest[] */
    private function discoverFromComposer(): array
    {
        $installedPath = $this->basePath . '/vendor/composer/installed.php';
        if (!file_exists($installedPath)) {
            return [];
        }

        $installed = require $installedPath;
        $manifests = [];

        foreach ($installed['packages'] ?? [] as $pkg) {
            $pkgPath     = $this->basePath . '/vendor/' . $pkg['name'];
            $manifestPath = $pkgPath . '/meraki.json';
            if (!file_exists($manifestPath)) {
                continue;
            }

            $data = json_decode(file_get_contents($manifestPath), true);
            if (is_array($data)) {
                $manifests[] = PluginManifest::fromArray($data, $pkgPath, 'composer');
            }
        }

        return $manifests;
    }

    /** @return PluginManifest[] */
    private function discoverFromPluginsDir(): array
    {
        $pluginsDir = $this->basePath . '/plugins';
        if (!is_dir($pluginsDir)) {
            return [];
        }

        $manifests = [];
        foreach (glob($pluginsDir . '/*/meraki.json') ?: [] as $manifestPath) {
            $data = json_decode(file_get_contents($manifestPath), true);
            if (is_array($data)) {
                $manifests[] = PluginManifest::fromArray($data, dirname($manifestPath), 'plugins');
            }
        }

        return $manifests;
    }

    private function writeCache(array $manifests): void
    {
        $cacheDir = dirname($this->cachePath);
        if (!is_dir($cacheDir)) {
            return;
        }

        $data     = array_map(fn(PluginManifest $m) => $m->toArray(), $manifests);
        $exported = var_export($data, true);
        file_put_contents($this->cachePath, "<?php\n\nreturn {$exported};\n");
    }
}
