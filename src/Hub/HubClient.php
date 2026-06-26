<?php

namespace Meraki\Core\Hub;

use Illuminate\Http\Client\Factory as HttpFactory;
use Meraki\Core\Contracts\HubClientInterface;
use Meraki\Core\Exceptions\HubException;

final class HubClient implements HubClientInterface
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string      $baseUrl,
        private readonly ?string     $token,
        private readonly int         $timeout = 10,
    ) {}

    public function ping(): bool
    {
        try {
            $response = $this->client()->get('/api/v1/ping');
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function getPlugin(string $hubId): HubPluginInfo
    {
        $response = $this->client()->get("/api/v1/plugins/{$hubId}");

        if ($response->notFound()) {
            throw HubException::pluginNotFound($hubId);
        }

        $this->assertSuccess($response, "getPlugin({$hubId})");

        return HubPluginInfo::fromArray($response->json());
    }

    public function checkUpdate(string $hubId, string $currentVersion): ?HubUpdateInfo
    {
        $response = $this->client()->get("/api/v1/plugins/{$hubId}/updates", [
            'version' => $currentVersion,
        ]);

        if ($response->status() === 204) {
            return null;
        }

        $this->assertSuccess($response, "checkUpdate({$hubId})");

        return HubUpdateInfo::fromArray($hubId, $currentVersion, $response->json());
    }

    public function checkUpdates(array $plugins): array
    {
        if (empty($plugins)) {
            return [];
        }

        $response = $this->client()->post('/api/v1/plugins/updates/batch', [
            'plugins' => array_map(
                fn ($hubId, $version) => ['hub_id' => $hubId, 'version' => $version],
                array_keys($plugins),
                array_values($plugins),
            ),
        ]);

        $this->assertSuccess($response, 'checkUpdates(batch)');

        $result = [];
        foreach ($response->json('results', []) as $item) {
            $hubId          = $item['hub_id'];
            $currentVersion = $plugins[$hubId] ?? '';
            $result[$hubId] = isset($item['latest_version'])
                ? HubUpdateInfo::fromArray($hubId, $currentVersion, $item)
                : null;
        }

        return $result;
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        $pending = $this->http
            ->baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->acceptJson();

        if ($this->token !== null) {
            $pending = $pending->withToken($this->token);
        }

        return $pending;
    }

    private function assertSuccess(
        \Illuminate\Http\Client\Response $response,
        string $context
    ): void {
        if (! $response->successful()) {
            throw HubException::requestFailed(
                $context,
                $response->status(),
                $response->json('message') ?? $response->body(),
            );
        }
    }
}
