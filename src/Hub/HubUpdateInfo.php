<?php

namespace Meraki\Core\Hub;

final class HubUpdateInfo
{
    public function __construct(
        public readonly string  $hubId,
        public readonly string  $newVersion,
        public readonly string  $currentVersion,
        public readonly ?string $changelogUrl = null,
    ) {}

    public static function fromArray(string $hubId, string $currentVersion, array $data): self
    {
        return new self(
            hubId:          $hubId,
            newVersion:     $data['latest_version'],
            currentVersion: $currentVersion,
            changelogUrl:   $data['changelog_url'] ?? null,
        );
    }
}
