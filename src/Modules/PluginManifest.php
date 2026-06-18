<?php

namespace Meraki\Core\Modules;

class PluginManifest
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $provider,
        public readonly string $config,
        public readonly string $basePath,
        public readonly string $source,
        public readonly ?string $version = null,
    ) {}

    public static function fromArray(array $data, string $basePath, string $source): self
    {
        foreach (['id', 'name', 'provider'] as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Plugin manifest missing required field: {$field}");
            }
        }

        return new self(
            id: $data['id'],
            name: $data['name'],
            provider: $data['provider'],
            config: $data['config'] ?? $data['id'],
            basePath: $basePath,
            source: $source,
            version: $data['version'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'provider' => $this->provider,
            'config'   => $this->config,
            'basePath' => $this->basePath,
            'source'   => $this->source,
            'version'  => $this->version,
        ];
    }
}
