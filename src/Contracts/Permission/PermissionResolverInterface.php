<?php

namespace Meraki\Core\Contracts\Permission;

interface PermissionResolverInterface
{
    public function has(string $permission): bool;
}
