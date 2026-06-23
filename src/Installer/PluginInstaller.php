<?php

namespace Meraki\Core\Installer;

use Meraki\Core\Exceptions\InvalidPluginArchiveException;
use Meraki\Core\Exceptions\PluginConflictException;
use Meraki\Core\Exceptions\PluginInstallException;
use Meraki\Core\Modules\PluginDiscovery;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class PluginInstaller
{
    public function __construct(
        private readonly string $pluginsBasePath,
        private readonly string $tempPath,
        private readonly ?PluginDiscovery $pluginDiscovery = null,
    ) {}

    public function installFromZip(string $zipPath): InstalledPlugin
    {
        if (!class_exists(ZipArchive::class)) {
            throw new PluginInstallException('ext-zip PHP extension is required.');
        }

        if (!file_exists($zipPath)) {
            throw new PluginInstallException("ZIP file not found: [{$zipPath}].");
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new InvalidPluginArchiveException("Cannot open ZIP archive: [{$zipPath}].");
        }

        try {
            $manifest = $this->readManifestFromZip($zip);
            $pluginId  = $manifest['id'];

            $this->assertNoConflict($pluginId);
            $this->assertNoPathTraversal($zip);

            $tempDir = $this->tempPath . '/meraki-install-' . uniqid();
            $zip->extractTo($tempDir);
        } finally {
            $zip->close();
        }

        $extractedRoot = $tempDir . '/' . $pluginId;
        $destination   = $this->pluginsBasePath . '/' . $pluginId;

        try {
            rename($extractedRoot, $destination);
        } finally {
            $this->removeDirectory($tempDir);
        }

        $this->pluginDiscovery?->clearCache();

        return new InstalledPlugin(
            id: $pluginId,
            name: $manifest['name'],
            version: $manifest['version'] ?? '',
            path: $destination,
        );
    }

    public function installFromHub(string $hubId): InstalledPlugin
    {
        $hubUrl = config('meraki.hub.url', 'https://hub.merakilabs.tech');
        $apiKey = config('meraki.hub.api_key');
        $timeout = (int) config('meraki.hub.timeout', 30);

        $request = Http::timeout($timeout);
        if ($apiKey) {
            $request = $request->withToken($apiKey);
        }

        $response = $request->get("{$hubUrl}/api/v1/plugins/{$hubId}");

        if (!$response->successful()) {
            throw new PluginInstallException(
                "Hub API error: {$response->status()} {$response->body()}"
            );
        }

        $data = $response->json();
        $downloadUrl = $data['download_url'] ?? null;

        if (!$downloadUrl) {
            throw new PluginInstallException("Hub API response missing download_url for plugin [{$hubId}].");
        }

        $tempZip = $this->tempPath . '/meraki-hub-' . $hubId . '.zip';

        try {
            $zipResponse = Http::timeout($timeout)->get($downloadUrl);

            if (!$zipResponse->successful()) {
                throw new PluginInstallException(
                    "Failed to download plugin ZIP: {$zipResponse->status()}"
                );
            }

            file_put_contents($tempZip, $zipResponse->body());

            return $this->installFromZip($tempZip);
        } finally {
            if (file_exists($tempZip)) {
                unlink($tempZip);
            }
        }
    }

    private function readManifestFromZip(ZipArchive $zip): array
    {
        $manifestEntry = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $parts = explode('/', ltrim($name, '/'));
            if (count($parts) === 2 && $parts[1] === 'meraki.json') {
                $manifestEntry = $name;
                break;
            }
        }

        if ($manifestEntry === null) {
            throw InvalidPluginArchiveException::missingManifest();
        }

        $content = $zip->getFromName($manifestEntry);
        $data    = json_decode($content, true);

        if (!is_array($data)) {
            throw InvalidPluginArchiveException::missingManifest();
        }

        foreach (['id', 'name', 'provider'] as $field) {
            if (empty($data[$field])) {
                throw new InvalidPluginArchiveException(
                    "Plugin manifest missing required field: [{$field}]."
                );
            }
        }

        return $data;
    }

    private function assertNoConflict(string $pluginId): void
    {
        if (is_dir($this->pluginsBasePath . '/' . $pluginId)) {
            throw PluginConflictException::alreadyInstalled($pluginId);
        }
    }

    private function assertNoPathTraversal(ZipArchive $zip): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_contains($name, '../') || str_starts_with($name, '/')) {
                throw InvalidPluginArchiveException::pathTraversal($name);
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }

        rmdir($dir);
    }
}
