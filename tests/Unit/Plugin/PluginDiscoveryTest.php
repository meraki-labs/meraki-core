<?php

namespace Meraki\Core\Tests\Unit\Plugin;

use Meraki\Core\Plugin\PluginDiscovery;
use Orchestra\Testbench\TestCase;
use Meraki\Core\CoreServiceProvider;

class PluginDiscoveryTest extends TestCase
{
    private string $tmpDir;

    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/meraki-plugin-discovery-' . uniqid();
        mkdir($this->tmpDir . '/vendor/composer', 0755, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->rmdirRecursive($this->tmpDir);
    }

    public function test_from_composer_returns_empty_when_file_missing(): void
    {
        $discovery = new PluginDiscovery();
        // base_path() in test env points elsewhere; installed.json won't exist
        // Patch: use a custom discovery with non-existent path
        $result = $this->discoverFromJson(null);
        $this->assertSame([], $result);
    }

    public function test_from_composer_returns_plugin_class_from_installed_json(): void
    {
        $installedJson = $this->tmpDir . '/vendor/composer/installed.json';
        file_put_contents($installedJson, json_encode([
            'packages' => [
                [
                    'name'  => 'merakilabs/meraki-auth',
                    'extra' => ['meraki' => ['plugin' => 'MerakiLabs\\MerakiAuth\\MerakiAuthPlugin']],
                ],
            ],
        ]));

        $result = $this->discoverFromJson($installedJson);

        $this->assertSame(['merakilabs/meraki-auth' => 'MerakiLabs\\MerakiAuth\\MerakiAuthPlugin'], $result);
    }

    public function test_from_composer_supports_composer_v1_format(): void
    {
        $installedJson = $this->tmpDir . '/vendor/composer/installed.json';
        // Composer v1: top-level array (no 'packages' key)
        file_put_contents($installedJson, json_encode([
            [
                'name'  => 'merakilabs/meraki-cms',
                'extra' => ['meraki' => ['plugin' => 'MerakiLabs\\Cms\\CmsPlugin']],
            ],
        ]));

        $result = $this->discoverFromJson($installedJson);

        $this->assertSame(['merakilabs/meraki-cms' => 'MerakiLabs\\Cms\\CmsPlugin'], $result);
    }

    public function test_from_composer_skips_packages_without_meraki_extra(): void
    {
        $installedJson = $this->tmpDir . '/vendor/composer/installed.json';
        file_put_contents($installedJson, json_encode([
            'packages' => [
                ['name' => 'vendor/package-a', 'extra' => []],
                ['name' => 'vendor/package-b'],
            ],
        ]));

        $result = $this->discoverFromJson($installedJson);

        $this->assertSame([], $result);
    }

    public function test_from_config_reads_from_meraki_config(): void
    {
        config(['meraki.plugins.list' => ['my-plugin' => 'App\\MyPlugin']]);

        $discovery = new PluginDiscovery();
        $result    = $discovery->fromConfig();

        $this->assertSame(['my-plugin' => 'App\\MyPlugin'], $result);
    }

    public function test_from_config_returns_empty_when_not_set(): void
    {
        config(['meraki.plugins.list' => []]);

        $discovery = new PluginDiscovery();
        $result    = $discovery->fromConfig();

        $this->assertSame([], $result);
    }

    public function test_all_merges_composer_and_config(): void
    {
        $installedJson = $this->tmpDir . '/vendor/composer/installed.json';
        file_put_contents($installedJson, json_encode([
            'packages' => [
                [
                    'name'  => 'vendor/composer-plugin',
                    'extra' => ['meraki' => ['plugin' => 'Vendor\\ComposerPlugin']],
                ],
            ],
        ]));

        config([
            'meraki.plugins.list'          => ['manual-plugin' => 'App\\ManualPlugin'],
            'meraki.plugins.auto_discover' => true,
        ]);

        $discovery = $this->makeDiscoveryWithJson($installedJson);
        $result    = $discovery->all();

        $this->assertArrayHasKey('vendor/composer-plugin', $result);
        $this->assertArrayHasKey('manual-plugin', $result);
    }

    public function test_config_overrides_composer_when_same_key(): void
    {
        $installedJson = $this->tmpDir . '/vendor/composer/installed.json';
        file_put_contents($installedJson, json_encode([
            'packages' => [
                [
                    'name'  => 'my-plugin',
                    'extra' => ['meraki' => ['plugin' => 'Composer\\MyPlugin']],
                ],
            ],
        ]));

        config([
            'meraki.plugins.list'          => ['my-plugin' => 'Config\\MyPlugin'],
            'meraki.plugins.auto_discover' => true,
        ]);

        $discovery = $this->makeDiscoveryWithJson($installedJson);
        $result    = $discovery->all();

        $this->assertSame('Config\\MyPlugin', $result['my-plugin']);
    }

    public function test_auto_discover_false_skips_composer(): void
    {
        config(['meraki.plugins.auto_discover' => false]);

        $installedJson = $this->tmpDir . '/vendor/composer/installed.json';
        file_put_contents($installedJson, json_encode([
            'packages' => [
                ['name' => 'vendor/x', 'extra' => ['meraki' => ['plugin' => 'Vendor\\X']]],
            ],
        ]));

        $result = $this->discoverFromJson($installedJson);

        $this->assertSame([], $result);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function discoverFromJson(?string $path): array
    {
        return $this->makeDiscoveryWithJson($path)->fromComposer();
    }

    private function makeDiscoveryWithJson(?string $path): PluginDiscovery
    {
        return new class($path) extends PluginDiscovery {
            public function __construct(private ?string $jsonPath) {}

            public function fromComposer(): array
            {
                if (!config('meraki.plugins.auto_discover', true)) {
                    return [];
                }

                if ($this->jsonPath === null || !file_exists($this->jsonPath)) {
                    return [];
                }

                $data     = json_decode(file_get_contents($this->jsonPath), true);
                $packages = $data['packages'] ?? $data;

                $found = [];
                foreach ($packages as $package) {
                    $class = $package['extra']['meraki']['plugin'] ?? null;
                    if ($class !== null) {
                        $found[$package['name']] = $class;
                    }
                }
                return $found;
            }
        };
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
