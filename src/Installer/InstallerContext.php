<?php

namespace Meraki\Core\Installer;

class InstallerContext
{
    public string $mode = 'install';
    public string $laravelVersion = '';
    public array $data = [];

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}
