<?php

namespace Meraki\Core\Tests\Installer;

use Meraki\Core\CoreServiceProvider;
use Meraki\Core\Exceptions\InvalidPluginArchiveException;
use Meraki\Core\Exceptions\PluginConflictException;
use Meraki\Core\Exceptions\PluginInstallException;
use Meraki\Core\Installer\InstalledPlugin;
use Meraki\Core\Installer\PluginInstaller;
use Meraki\Core\Modules\PluginDiscovery;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Http;

class PluginInstallerTest extends TestCase
{
    private string $pluginsDir;
    private string $tempDir;
    private PluginDiscovery $discovery;
    private PluginInstaller $installer;

    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $base = sys_get_temp_dir() . '/meraki-installer-test-' . uniqid();
        $this->pluginsDir = $base . '/plugins';
        $this->tempDir    = $base . '/tmp';
        mkdir($this->pluginsDir, 0755, true);
        mkdir($this->tempDir, 0755, true);
        mkdir($base . '/cache', 0755, true);

        $this->discovery = new PluginDiscovery(
            basePath: $base,
            cachePath: $base . '/cache/meraki-plugins.php',
        );

        $this->installer = new PluginInstaller(
            pluginsBasePath: $this->pluginsDir,
            tempPath: $this->tempDir,
            pluginDiscovery: $this->discovery,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $base = dirname($this->pluginsDir);
        $this->removeDirectory($base);
    }

    public function test_install_from_valid_zip_creates_plugin_directory(): void
    {
        $zipPath = __DIR__ . '/../fixtures/valid-plugin.zip';

        $result = $this->installer->installFromZip($zipPath);

        $this->assertInstanceOf(InstalledPlugin::class, $result);
        $this->assertSame('valid-plugin', $result->id);
        $this->assertSame('Valid Plugin', $result->name);
        $this->assertSame('1.0.0', $result->version);
        $this->assertDirectoryExists($this->pluginsDir . '/valid-plugin');
        $this->assertFileExists($this->pluginsDir . '/valid-plugin/meraki.json');
    }

    public function test_install_from_zip_missing_manifest_throws(): void
    {
        $this->expectException(InvalidPluginArchiveException::class);
        $this->expectExceptionMessage('missing meraki.json');

        $zipPath = __DIR__ . '/../fixtures/invalid-no-manifest.zip';
        $this->installer->installFromZip($zipPath);
    }

    public function test_install_from_zip_with_path_traversal_throws(): void
    {
        $this->expectException(InvalidPluginArchiveException::class);
        $this->expectExceptionMessage('Unsafe entry');

        $zipPath = __DIR__ . '/../fixtures/path-traversal.zip';
        $this->installer->installFromZip($zipPath);
    }

    public function test_install_from_zip_when_plugin_already_exists_throws(): void
    {
        $zipPath = __DIR__ . '/../fixtures/valid-plugin.zip';
        $this->installer->installFromZip($zipPath);

        $this->expectException(PluginConflictException::class);
        $this->expectExceptionMessage('valid-plugin');

        $this->installer->installFromZip($zipPath);
    }

    public function test_install_from_zip_clears_discovery_cache(): void
    {
        // Warm the cache
        $this->discovery->discover();

        $zipPath = __DIR__ . '/../fixtures/valid-plugin.zip';
        $this->installer->installFromZip($zipPath);

        // After install the in-memory cache must be null (re-discoverable)
        $cacheProperty = new \ReflectionProperty(PluginDiscovery::class, 'cached');
        $cacheProperty->setAccessible(true);
        $this->assertNull($cacheProperty->getValue($this->discovery));
    }

    public function test_install_from_zip_file_not_found_throws(): void
    {
        $this->expectException(PluginInstallException::class);
        $this->expectExceptionMessage('ZIP file not found');

        $this->installer->installFromZip('/nonexistent/path/plugin.zip');
    }

    public function test_install_from_hub_404_throws(): void
    {
        Http::fake([
            'https://hub.merakilabs.tech/api/v1/plugins/nonexistent' => Http::response('Not Found', 404),
        ]);

        $this->expectException(PluginInstallException::class);
        $this->expectExceptionMessage('Hub API error: 404');

        $this->installer->installFromHub('nonexistent');
    }

    public function test_install_from_hub_downloads_and_installs(): void
    {
        $zipContent = file_get_contents(__DIR__ . '/../fixtures/valid-plugin.zip');

        Http::fake([
            'https://hub.merakilabs.tech/api/v1/plugins/valid-plugin' => Http::response([
                'id'           => 'valid-plugin',
                'name'         => 'Valid Plugin',
                'version'      => '1.0.0',
                'download_url' => 'https://hub.merakilabs.tech/downloads/valid-plugin-1.0.0.zip',
            ], 200),
            'https://hub.merakilabs.tech/downloads/valid-plugin-1.0.0.zip' => Http::response($zipContent, 200),
        ]);

        $result = $this->installer->installFromHub('valid-plugin');

        $this->assertInstanceOf(InstalledPlugin::class, $result);
        $this->assertSame('valid-plugin', $result->id);
        $this->assertDirectoryExists($this->pluginsDir . '/valid-plugin');
    }

    public function test_install_from_hub_cleans_temp_zip_on_success(): void
    {
        $zipContent = file_get_contents(__DIR__ . '/../fixtures/valid-plugin.zip');

        Http::fake([
            'https://hub.merakilabs.tech/api/v1/plugins/valid-plugin' => Http::response([
                'id'           => 'valid-plugin',
                'name'         => 'Valid Plugin',
                'version'      => '1.0.0',
                'download_url' => 'https://hub.merakilabs.tech/downloads/valid-plugin-1.0.0.zip',
            ], 200),
            'https://hub.merakilabs.tech/downloads/valid-plugin-1.0.0.zip' => Http::response($zipContent, 200),
        ]);

        $this->installer->installFromHub('valid-plugin');

        $this->assertFileDoesNotExist($this->tempDir . '/meraki-hub-valid-plugin.zip');
    }

    public function test_install_from_hub_cleans_temp_zip_on_failure(): void
    {
        $zipContent = file_get_contents(__DIR__ . '/../fixtures/invalid-no-manifest.zip');

        Http::fake([
            'https://hub.merakilabs.tech/api/v1/plugins/bad-plugin' => Http::response([
                'id'           => 'bad-plugin',
                'name'         => 'Bad Plugin',
                'version'      => '1.0.0',
                'download_url' => 'https://hub.merakilabs.tech/downloads/bad-plugin-1.0.0.zip',
            ], 200),
            'https://hub.merakilabs.tech/downloads/bad-plugin-1.0.0.zip' => Http::response($zipContent, 200),
        ]);

        try {
            $this->installer->installFromHub('bad-plugin');
        } catch (InvalidPluginArchiveException) {
            // expected
        }

        $this->assertFileDoesNotExist($this->tempDir . '/meraki-hub-bad-plugin.zip');
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
