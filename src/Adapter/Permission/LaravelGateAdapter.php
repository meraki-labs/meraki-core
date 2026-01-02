<?php
namespace Meraki\Core\Adapter\Permission;

use Illuminate\Support\Facades\Gate;
use Meraki\Core\Contracts\Permission\PermissionResolverInterface;

class LaravelGateAdapter implements PermissionResolverInterface
{
    public function has(string $permission): bool
    {
        return Gate::allows($permission);
    }
}
