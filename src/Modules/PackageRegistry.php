<?php

namespace Meraki\Core\Modules;

class PackageRegistry
{
    protected array $packages = [];

    public function register(string $name, array $meta = []): void
    {
        $this->packages[$name] = $meta;
    }

    public function all(): array
    {
        return $this->packages;
    }

    public function has(string $name): bool
    {
        return isset($this->packages[$name]);
    }
}
