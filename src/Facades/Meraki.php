<?php

namespace Meraki\Core\Facades;

use Illuminate\Support\Facades\Facade;
use Meraki\Core\CoreManager;

/**
 * @method static \Meraki\Core\Contracts\AuthDriver auth()
 * @method static \Meraki\Core\Contracts\PermissionDriver permission()
 * @method static bool can(string $permission, mixed $user = null)
 * @method static void extend(string $capability, string $name, \Closure $factory)
 * @method static \Meraki\Core\Modules\PackageRegistry packages()
 * @method static \Meraki\Core\Plugin\PluginLoader plugins()
 *
 * @see CoreManager
 */
class Meraki extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CoreManager::class;
    }
}
