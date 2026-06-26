<?php

namespace Meraki\Core\Tests\Hub;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Meraki\Core\Contracts\HubClientInterface;
use Meraki\Core\Exceptions\HubException;
use Meraki\Core\Hub\HubClient;
use Meraki\Core\Hub\HubPluginInfo;
use Meraki\Core\Hub\HubUpdateInfo;
use Meraki\Core\Testing\MerakiTestCase;

class HubClientTest extends MerakiTestCase
{
    private function makeClient(): HubClient
    {
        return new HubClient(
            http:    $this->app->make(HttpFactory::class),
            baseUrl: 'https://hub.test',
            token:   'test-token',
            timeout: 5,
        );
    }

    // Test 1: ping() khi hub trả 200
    public function test_ping_returns_true_when_hub_responds_200(): void
    {
        Http::fake(['https://hub.test/api/v1/ping' => Http::response(null, 200)]);

        $this->assertTrue($this->makeClient()->ping());
    }

    // Test 2: ping() khi hub không phản hồi
    public function test_ping_returns_false_when_hub_throws_exception(): void
    {
        Http::fake(['https://hub.test/api/v1/ping' => fn () => throw new \RuntimeException('connection refused')]);

        $this->assertFalse($this->makeClient()->ping());
    }

    // Test 3: getPlugin() khi hub trả 200 với JSON hợp lệ
    public function test_get_plugin_returns_hub_plugin_info_on_success(): void
    {
        Http::fake([
            'https://hub.test/api/v1/plugins/abc' => Http::response([
                'hub_id'         => 'abc',
                'name'           => 'My Plugin',
                'latest_version' => '2.0.0',
                'description'    => 'A test plugin',
                'author'         => 'TestAuthor',
                'changelog_url'  => 'https://example.com/changelog',
            ], 200),
        ]);

        $info = $this->makeClient()->getPlugin('abc');

        $this->assertInstanceOf(HubPluginInfo::class, $info);
        $this->assertSame('abc', $info->hubId);
        $this->assertSame('My Plugin', $info->name);
        $this->assertSame('2.0.0', $info->latestVersion);
        $this->assertSame('A test plugin', $info->description);
        $this->assertSame('TestAuthor', $info->author);
        $this->assertSame('https://example.com/changelog', $info->changelogUrl);
    }

    // Test 4: getPlugin() khi hub trả 404
    public function test_get_plugin_throws_hub_exception_on_404(): void
    {
        Http::fake([
            'https://hub.test/api/v1/plugins/xyz' => Http::response(null, 404),
        ]);

        $this->expectException(HubException::class);
        $this->expectExceptionMessage('Plugin [xyz] not found on hub.');

        $this->makeClient()->getPlugin('xyz');
    }

    // Test 5: getPlugin() khi hub trả 500
    public function test_get_plugin_throws_hub_exception_on_500(): void
    {
        Http::fake([
            'https://hub.test/api/v1/plugins/abc' => Http::response(['message' => 'server error'], 500),
        ]);

        $this->expectException(HubException::class);
        $this->expectExceptionMessage('Hub request failed [getPlugin(abc)]: HTTP 500');

        $this->makeClient()->getPlugin('abc');
    }

    // Test 6: checkUpdate() khi hub trả 204
    public function test_check_update_returns_null_when_already_latest(): void
    {
        Http::fake([
            'https://hub.test/api/v1/plugins/abc/updates*' => Http::response(null, 204),
        ]);

        $result = $this->makeClient()->checkUpdate('abc', '1.0.0');

        $this->assertNull($result);
    }

    // Test 7: checkUpdate() khi hub trả 200 với version mới
    public function test_check_update_returns_hub_update_info_when_update_available(): void
    {
        Http::fake([
            'https://hub.test/api/v1/plugins/abc/updates*' => Http::response([
                'latest_version' => '2.0.0',
                'changelog_url'  => 'https://example.com/v2',
            ], 200),
        ]);

        $info = $this->makeClient()->checkUpdate('abc', '1.0.0');

        $this->assertInstanceOf(HubUpdateInfo::class, $info);
        $this->assertSame('abc', $info->hubId);
        $this->assertSame('2.0.0', $info->newVersion);
        $this->assertSame('1.0.0', $info->currentVersion);
        $this->assertSame('https://example.com/v2', $info->changelogUrl);
    }

    // Test 8: checkUpdates() batch
    public function test_check_updates_returns_map_with_update_info_and_nulls(): void
    {
        Http::fake([
            'https://hub.test/api/v1/plugins/updates/batch' => Http::response([
                'results' => [
                    ['hub_id' => 'a', 'latest_version' => '2.0', 'changelog_url' => null],
                    ['hub_id' => 'b'],
                ],
            ], 200),
        ]);

        $result = $this->makeClient()->checkUpdates(['a' => '1.0', 'b' => '2.0']);

        $this->assertArrayHasKey('a', $result);
        $this->assertArrayHasKey('b', $result);
        $this->assertInstanceOf(HubUpdateInfo::class, $result['a']);
        $this->assertSame('2.0', $result['a']->newVersion);
        $this->assertSame('1.0', $result['a']->currentVersion);
        $this->assertNull($result['b']);
    }

    // Test 9: checkUpdates([]) không gọi HTTP
    public function test_check_updates_with_empty_array_returns_empty_without_http_call(): void
    {
        Http::fake();

        $result = $this->makeClient()->checkUpdates([]);

        $this->assertSame([], $result);
        Http::assertNothingSent();
    }

    // Test 10: HubClient đăng ký đúng trong container
    public function test_hub_client_is_registered_in_container(): void
    {
        $instance = $this->app->make(HubClientInterface::class);

        $this->assertInstanceOf(HubClient::class, $instance);
    }
}
