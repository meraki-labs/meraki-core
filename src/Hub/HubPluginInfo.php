<?php

namespace Meraki\Core\Hub;

final class HubPluginInfo
{
    public function __construct(
        public readonly string  $hubId,
        public readonly string  $name,
        public readonly string  $latestVersion,
        public readonly string  $description,
        public readonly string  $author,
        public readonly ?string $changelogUrl = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            hubId:         $data['hub_id'],
            name:          $data['name'],
            latestVersion: $data['latest_version'],
            description:   $data['description'] ?? '',
            author:        $data['author']       ?? '',
            changelogUrl:  $data['changelog_url'] ?? null,
        );
    }
}
