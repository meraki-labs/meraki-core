<?php

namespace Meraki\Core\Installer\State;

class MerakiState
{
    public bool $installed = false;
    public string $laravelVersion = '';
    public ?string $installedAt = null;

    public static function load(): self
    {
        $state = new self();
        $file = config('meraki.state_file');

        if (! file_exists($file)) {
            return $state;
        }

        $data = json_decode(file_get_contents($file), true) ?? [];

        $state->installed = ($data['status'] ?? null) === 'installed';
        $state->laravelVersion = $data['laravel_version'] ?? '';
        $state->installedAt = $data['installed_at'] ?? null;

        return $state;
    }
}
