<?php

namespace Meraki\Core\Adapters;

use Meraki\Core\Contracts\PermissionDriver;
use Illuminate\Support\Facades\Gate;

class LaravelGateAdapter implements PermissionDriver
{
    public function can(string $permission, mixed $user = null): bool
    {
        if ($user !== null) {
            return Gate::forUser($user)->allows($permission);
        }

        return Gate::allows($permission);
    }
}
