<?php

namespace Meraki\Core\Plugin;

final class PluginMeta
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $version,
        public readonly string  $id          = '',
        public readonly string  $description = '',
        public readonly string  $author      = '',
        public readonly array   $requires    = [],
        public readonly ?string $config      = null,
        public readonly ?string $hubId       = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:        $data['name']        ?? '',
            version:     $data['version']     ?? '',
            id:          $data['id']          ?? '',
            description: $data['description'] ?? '',
            author:      $data['author']      ?? '',
            requires:    $data['requires']    ?? [],
            config:      $data['config']      ?? null,
            hubId:       $data['hub_id']      ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'version'     => $this->version,
            'description' => $this->description,
            'author'      => $this->author,
            'requires'    => $this->requires,
            'config'      => $this->config,
            'hub_id'      => $this->hubId,
        ];
    }
}
