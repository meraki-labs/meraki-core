<?php

use Meraki\Core\CoreManager;
use Meraki\Core\Modules\PermissionRegistry;

if (!function_exists('meraki')) {
    function meraki(): CoreManager
    {
        return app(CoreManager::class);
    }
}

if (!function_exists('meraki_can')) {
    function meraki_can(string $permission, mixed $user = null): bool
    {
        return meraki()->can($permission, $user);
    }
}

if (!function_exists('meraki_permissions')) {
    function meraki_permissions(): array
    {
        return app(PermissionRegistry::class)->all();
    }
}

if (!function_exists('meraki_version')) {
    function meraki_version(): string
    {
        return \Meraki\Core\Installer\State\MerakiState::load()->laravelVersion ?: 'unknown';
    }
}
