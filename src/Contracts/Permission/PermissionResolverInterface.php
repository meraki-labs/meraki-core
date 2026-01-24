<?php

namespace Meraki\Core\Contracts\Permission;

/**
 * @Author DatPA
 */
interface PermissionResolverInterface
{
    /**
     * @param string $permission
     * @return bool
     */
    public function has(string $permission): bool;
}
