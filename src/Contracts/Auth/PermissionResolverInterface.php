<?php

namespace Meraki\Core\Contracts\Auth;

interface PermissionResolverInterface
{
    public function has(string $permission): bool;
}
