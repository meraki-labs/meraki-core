<?php
namespace Meraki\Core\Adapter\Permission;

use Illuminate\Support\Facades\Gate;
use Meraki\Core\Contracts\Permission\PermissionResolverInterface;

/**
 * @Author DatPA
 */
class LaravelGateAdapter implements PermissionResolverInterface
{
    /**
     * @param string $permission
     * @return bool
     */
    public function has(string $permission): bool
    {
        return Gate::allows($permission);
    }
}
