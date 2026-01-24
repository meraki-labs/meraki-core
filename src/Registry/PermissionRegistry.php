<?php
namespace Meraki\Core\Registry;

/**
 * @Author DatPA
 */
class PermissionRegistry
{
    protected static array $permissions = [];

    /**
     * @param array $permissions
     * @return void
     */
    public static function register(array $permissions): void
    {
        self::$permissions = array_merge(self::$permissions, $permissions);
    }

    /**
     * @return array
     */
    public static function all(): array
    {
        return self::$permissions;
    }
}
