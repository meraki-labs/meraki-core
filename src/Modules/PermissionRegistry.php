<?php
/**
 * @internal
 * Managed by Meraki Core Team
 */

namespace Meraki\Core\Modules;

class PermissionRegistry
{
    protected array $permissions = [];

    /**
     * @param array $permissions
     * @return void
     */
    public function register(array $permissions): void
    {
        foreach ($permissions as $permission) {
            $this->permissions[] = $permission;
        }
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->permissions;
    }

    /**
     * @param string $module
     * @return array
     */
    public function byModule(string $module): array
    {
        return array_filter(
            $this->permissions,
            fn($p) => ($p['module'] ?? null) === $module
        );
    }
}
