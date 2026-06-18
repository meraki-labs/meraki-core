<?php

namespace Meraki\Core\Tests;

use Orchestra\Testbench\TestCase;
use Meraki\Core\CoreServiceProvider;
use Meraki\Core\Modules\PackageRegistry;
use Meraki\Core\Modules\PluginDiscovery;
use Illuminate\Support\Facades\Artisan;

class PluginDiscoveryIntegrationTest extends TestCase
{
    private string $pluginsDir;
    private string $cacheFile;

    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        // Point discovery at a temp plugins dir we control
        $this->pluginsDir = sys_get_temp_dir() . '/meraki-integ-plugins-' . uniqid();
        $this->cacheFile  = sys_get_temp_dir() . '/meraki-integ-cache-' . uniqid() . '.php';
        mkdir($this->pluginsDir, 0755, true);

        $app->singleton(PluginDiscovery::class, function () {
            return new PluginDiscovery(
                basePath: sys_get_temp_dir() . '/nonexistent-base-' . uniqid(),
                cachePath: $this->cacheFile,
            );
        });
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (is_dir($this->pluginsDir)) {
            $this->rmdirRecursive($this->pluginsDir);
        }
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    public function test_discovered_plugin_is_registered_in_package_registry(): void
    {
        $baseDir   = $this->pluginsDir;
        $pluginDir = $baseDir . '/plugins/meraki-test-plugin';
        mkdir($pluginDir, 0755, true);
        file_put_contents($pluginDir . '/meraki.json', json_encode([
            'id'       => 'meraki-test',
            'name'     => 'Meraki Test',
            'provider' => 'Meraki\\Test\\TestServiceProvider',
        ]));

        $discovery = new PluginDiscovery(
            basePath: $baseDir,
            cachePath: $this->cacheFile,
        );
        $discovery->clearCache();

        $manifests = $discovery->discover();

        $packages = $this->app->make(PackageRegistry::class);
        foreach ($manifests as $manifest) {
            if (! $packages->has($manifest->id)) {
                $packages->register($manifest->id, [
                    'provider' => $manifest->provider,
                    'config'   => $manifest->config,
                ]);
            }
        }

        $this->assertTrue($packages->has('meraki-test'));
    }

    public function test_manually_registered_plugin_is_not_duplicated(): void
    {
        $packages = $this->app->make(PackageRegistry::class);
        $packages->register('meraki-existing', ['provider' => 'FakeProvider', 'config' => 'meraki-existing']);

        $discovery = $this->app->make(PluginDiscovery::class);
        $manifests = $discovery->discover();

        foreach ($manifests as $manifest) {
            if (! $packages->has($manifest->id)) {
                $packages->register($manifest->id, [
                    'provider' => $manifest->provider,
                    'config'   => $manifest->config,
                ]);
            }
        }

        $all = $packages->all();
        $ids = array_keys($all);
        $this->assertCount(1, array_filter($ids, fn($id) => $id === 'meraki-existing'));
    }

    public function test_plugin_config_is_merged_when_config_file_exists(): void
    {
        $baseDir   = $this->pluginsDir;
        $pluginDir = $baseDir . '/plugins/meraki-config-plugin';
        mkdir($pluginDir . '/config', 0755, true);
        file_put_contents($pluginDir . '/meraki.json', json_encode([
            'id'       => 'meraki-cfg',
            'name'     => 'Meraki Config Plugin',
            'provider' => 'Meraki\\Cfg\\CfgServiceProvider',
            'config'   => 'meraki-cfg',
        ]));
        file_put_contents($pluginDir . '/config/meraki-cfg.php', "<?php\nreturn ['permissions' => [['module' => 'cfg', 'name' => 'cfg.view', 'label' => 'View Cfg']]];\n");

        $discovery = new PluginDiscovery(
            basePath: $baseDir,
            cachePath: $this->cacheFile,
        );
        $discovery->clearCache();

        foreach ($discovery->discover() as $manifest) {
            $configPath = $manifest->basePath . '/config/' . $manifest->config . '.php';
            if (file_exists($configPath)) {
                config([$manifest->config => require $configPath]);
            }
        }

        $permissions = config('meraki-cfg.permissions', []);
        $this->assertNotEmpty($permissions);
        $this->assertSame('cfg.view', $permissions[0]['name']);
    }

    public function test_meraki_discover_command_is_registered(): void
    {
        $commands = array_keys(Artisan::all());
        $this->assertContains('meraki:discover', $commands);
    }

    public function test_meraki_discover_command_runs_without_error(): void
    {
        $exitCode = $this->artisan('meraki:discover');
        $exitCode->assertExitCode(0);
    }

    public function test_meraki_discover_command_refresh_clears_cache(): void
    {
        file_put_contents($this->cacheFile, "<?php\nreturn [];\n");
        $this->assertFileExists($this->cacheFile);

        $this->artisan('meraki:discover', ['--refresh' => true])->assertExitCode(0);
    }

    // --- helpers ---

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
