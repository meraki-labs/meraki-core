<?php

namespace Meraki\Core\Tests;

use Orchestra\Testbench\TestCase;
use Meraki\Core\CoreServiceProvider;
use Meraki\Core\Contracts\Plugin;
use Meraki\Core\Plugins\AbstractPlugin;
use Meraki\Core\Plugins\Discovery\DirectoryDiscoverer;
use Meraki\Core\Plugins\Discovery\ComposerDiscoverer;
use Illuminate\Contracts\Foundation\Application;

class PluginDiscoveryTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    // ── DirectoryDiscoverer ──────────────────────────────────────────────────

    public function test_directory_discoverer_returns_empty_for_nonexistent_path(): void
    {
        $discoverer = new DirectoryDiscoverer('/non/existent/path');
        $this->assertSame([], $discoverer->discover());
    }

    public function test_directory_discoverer_discovers_plugin_from_fixture(): void
    {
        $pluginsDir = $this->createPluginFixture('my-feature', FixturePlugin::class);

        $discoverer = new DirectoryDiscoverer($pluginsDir);
        $plugins    = $discoverer->discover();

        $this->assertCount(1, $plugins);
        $this->assertSame('fixture-plugin', $plugins[0]->id());

        $this->removePluginFixture($pluginsDir);
    }

    public function test_directory_discoverer_skips_directories_without_manifest(): void
    {
        $dir = sys_get_temp_dir() . '/meraki-discover-test-' . uniqid();
        mkdir($dir . '/no-manifest', 0755, true);

        $discoverer = new DirectoryDiscoverer($dir);
        $plugins    = $discoverer->discover();

        $this->assertSame([], $plugins);

        rmdir($dir . '/no-manifest');
        rmdir($dir);
    }

    // ── ComposerDiscoverer ───────────────────────────────────────────────────

    public function test_composer_discoverer_returns_empty_for_nonexistent_file(): void
    {
        $discoverer = new ComposerDiscoverer('/non/existent/installed.json');
        $this->assertSame([], $discoverer->discover());
    }

    public function test_composer_discoverer_parses_installed_json_fixture(): void
    {
        $installedJson = $this->createInstalledJsonFixture(FixturePlugin::class);

        $discoverer = new ComposerDiscoverer($installedJson);
        $plugins    = $discoverer->discover();

        $this->assertCount(1, $plugins);
        $this->assertSame('fixture-plugin', $plugins[0]->id());

        unlink($installedJson);
    }

    public function test_composer_discoverer_ignores_non_meraki_plugin_packages(): void
    {
        $data = [
            'packages' => [
                ['name' => 'vendor/regular-package', 'type' => 'library'],
            ],
        ];

        $file = sys_get_temp_dir() . '/installed-' . uniqid() . '.json';
        file_put_contents($file, json_encode($data));

        $discoverer = new ComposerDiscoverer($file);
        $plugins    = $discoverer->discover();

        $this->assertSame([], $plugins);

        unlink($file);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function createPluginFixture(string $slug, string $fqcn): string
    {
        $dir = sys_get_temp_dir() . '/meraki-plugins-' . uniqid();
        mkdir($dir . '/' . $slug, 0755, true);
        file_put_contents($dir . '/' . $slug . '/plugin.php', '<?php return ' . $fqcn . '::class;');
        return $dir;
    }

    private function removePluginFixture(string $dir): void
    {
        foreach (glob($dir . '/*/*') as $file) {
            unlink($file);
        }
        foreach (glob($dir . '/*') as $subdir) {
            rmdir($subdir);
        }
        rmdir($dir);
    }

    private function createInstalledJsonFixture(string $fqcn): string
    {
        $data = [
            'packages' => [
                [
                    'name'  => 'vendor/fixture-plugin',
                    'type'  => 'meraki-plugin',
                    'extra' => [
                        'meraki-plugin' => ['class' => $fqcn],
                    ],
                ],
            ],
        ];

        $file = sys_get_temp_dir() . '/installed-' . uniqid() . '.json';
        file_put_contents($file, json_encode($data));
        return $file;
    }
}

class FixturePlugin extends AbstractPlugin
{
    public function id(): string      { return 'fixture-plugin'; }
    public function name(): string    { return 'Fixture Plugin'; }
    public function version(): string { return '1.0.0'; }
}
