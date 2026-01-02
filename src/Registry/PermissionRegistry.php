<?php
namespace Meraki\Core\Registry;

class PermissionRegistry
{
    protected static array $permissions = [];

    public static function register(array $permissions): void
    {
        self::$permissions = array_merge(self::$permissions, $permissions);
    }

    public static function all(): array
    {
        return self::$permissions;
    }
}
