<?php

namespace Meraki\Core\Tests;

use Meraki\Core\Exceptions\DuplicatePluginIdException;
use Meraki\Core\Modules\PluginDiscovery;
use Meraki\Core\Modules\PluginManifest;
use PHPUnit\Framework\TestCase;

class PluginDiscoveryTest extends TestCase
{
    private string $tmpDir;
    private string $cacheFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir  = sys_get_temp_dir() . '/meraki-discovery-test-' . uniqid();
        $this->cacheFile = $this->tmpDir . '/cache/meraki-plugins.php';
        mkdir($this->tmpDir . '/cache', 0755, true);
        mkdir($this->tmpDir . '/vendor/composer', 0755, true);
        mkdir($this->tmpDir . '/plugins', 0755, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->rmdirRecursive($this->tmpDir);
    }

    public function test_discovers_plugin_from_composer_installed(): void
    {
        $this->createComposerPackage('meraki-labs/meraki-cms', [
            'id' => 'meraki-cms', 'name' => 'Meraki CMS',
            'provider' => 'Meraki\\Cms\\CmsServiceProvider', 'version' => '1.0.0',
        ]);

        $discovery = $this->makeDiscovery();
        $manifests = $discovery->discover();

        $this->assertCount(1, $manifests);
        $this->assertInstanceOf(PluginManifest::class, $manifests[0]);
        $this->assertSame('meraki-cms', $manifests[0]->id);
        $this->assertSame('composer', $manifests[0]->source);
    }

    public function test_discovers_plugin_from_plugins_directory(): void
    {
        $this->createPluginsDirPlugin('meraki-auth', [
            'id' => 'meraki-auth', 'name' => 'Meraki Auth',
            'provider' => 'Meraki\\Auth\\AuthServiceProvider',
        ]);

        $discovery = $this->makeDiscovery();
        $manifests = $discovery->discover();

        $this->assertCount(1, $manifests);
        $this->assertSame('meraki-auth', $manifests[0]->id);
        $this->assertSame('plugins', $manifests[0]->source);
    }

    public function test_composer_package_without_meraki_json_is_excluded(): void
    {
        $this->writeInstalledPhp([
            ['name' => 'vendor/no-meraki-pkg'],
        ]);

        mkdir($this->tmpDir . '/vendor/vendor/no-meraki-pkg', 0755, true);

        $discovery = $this->makeDiscovery();
        $manifests = $discovery->discover();

        $this->assertCount(0, $manifests);
    }

    public function test_duplicate_plugin_id_throws_exception(): void
    {
        $this->createComposerPackage('meraki-labs/meraki-cms', [
            'id' => 'meraki-cms', 'name' => 'Meraki CMS',
            'provider' => 'Meraki\\Cms\\CmsServiceProvider',
        ]);

        $this->createPluginsDirPlugin('meraki-cms-zip', [
            'id' => 'meraki-cms', 'name' => 'Meraki CMS ZIP',
            'provider' => 'Meraki\\Cms\\CmsServiceProvider',
        ]);

        $discovery = $this->makeDiscovery();
        $this->expectException(DuplicatePluginIdException::class);
        $this->expectExceptionMessageMatches('/meraki-cms/');
        $discovery->discover();
    }

    public function test_cache_is_written_and_read(): void
    {
        $this->createComposerPackage('meraki-labs/meraki-cms', [
            'id' => 'meraki-cms', 'name' => 'Meraki CMS',
            'provider' => 'Meraki\\Cms\\CmsServiceProvider',
        ]);

        $discovery = $this->makeDiscovery();
        $discovery->discover();

        $this->assertFileExists($this->cacheFile);

        // New instance reads from cache
        $discovery2 = $this->makeDiscovery();
        $manifests  = $discovery2->discover();
        $this->assertCount(1, $manifests);
        $this->assertSame('meraki-cms', $manifests[0]->id);
    }

    public function test_clear_cache_removes_cache_file(): void
    {
        $this->createComposerPackage('meraki-labs/meraki-cms', [
            'id' => 'meraki-cms', 'name' => 'Meraki CMS',
            'provider' => 'Meraki\\Cms\\CmsServiceProvider',
        ]);

        $discovery = $this->makeDiscovery();
        $discovery->discover();

        $this->assertFileExists($this->cacheFile);

        $discovery->clearCache();
        $this->assertFileDoesNotExist($this->cacheFile);
    }

    public function test_config_defaults_to_plugin_id(): void
    {
        $this->createComposerPackage('meraki-labs/meraki-cms', [
            'id' => 'meraki-cms', 'name' => 'Meraki CMS',
            'provider' => 'Meraki\\Cms\\CmsServiceProvider',
        ]);

        $discovery = $this->makeDiscovery();
        $manifests = $discovery->discover();

        $this->assertSame('meraki-cms', $manifests[0]->config);
    }

    public function test_config_key_can_be_overridden_in_manifest(): void
    {
        $this->createComposerPackage('meraki-labs/meraki-cms', [
            'id' => 'meraki-cms', 'name' => 'Meraki CMS',
            'provider' => 'Meraki\\Cms\\CmsServiceProvider',
            'config' => 'cms-custom',
        ]);

        $discovery = $this->makeDiscovery();
        $manifests = $discovery->discover();

        $this->assertSame('cms-custom', $manifests[0]->config);
    }

    // --- helpers ---

    private function makeDiscovery(): PluginDiscovery
    {
        return new PluginDiscovery(
            basePath: $this->tmpDir,
            cachePath: $this->cacheFile,
        );
    }

    private function createComposerPackage(string $name, array $manifest): void
    {
        $pkgDir = $this->tmpDir . '/vendor/' . $name;
        mkdir($pkgDir, 0755, true);
        file_put_contents($pkgDir . '/meraki.json', json_encode($manifest));

        $existing = [];
        $installedPath = $this->tmpDir . '/vendor/composer/installed.php';
        if (file_exists($installedPath)) {
            $data = require $installedPath;
            $existing = $data['packages'] ?? [];
        }

        $existing[] = ['name' => $name];
        $this->writeInstalledPhp($existing);
    }

    private function createPluginsDirPlugin(string $dir, array $manifest): void
    {
        $pluginDir = $this->tmpDir . '/plugins/' . $dir;
        mkdir($pluginDir, 0755, true);
        file_put_contents($pluginDir . '/meraki.json', json_encode($manifest));
    }

    private function writeInstalledPhp(array $packages): void
    {
        $exported = var_export(['packages' => $packages], true);
        file_put_contents(
            $this->tmpDir . '/vendor/composer/installed.php',
            "<?php\nreturn {$exported};\n"
        );
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
        }
        rmdir($dir);
    }
}
