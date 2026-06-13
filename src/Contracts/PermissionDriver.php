<?php

namespace Meraki\Core\Contracts;

interface PermissionDriver
{
    public function can(string $permission, mixed $user = null): bool;
}
