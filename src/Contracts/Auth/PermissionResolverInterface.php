<?php

namespace Meraki\Core\Contracts\Auth;

/**
 * @Author DatPA
 */
interface PermissionResolverInterface
{
    public function has(string $permission): bool;
}
