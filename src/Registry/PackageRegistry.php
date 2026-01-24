<?php

namespace Meraki\Core\Registry;

/**
 * @Author DatPA
 */
class PackageRegistry
{
    protected static array $packages = [];

    /**
     * @param string $name
     * @param array $meta
     * @return void
     */
    public static function register(string $name, array $meta = []): void
    {
        self::$packages[$name] = $meta;
    }

    /**
     * @return array
     */
    public static function all(): array
    {
        return self::$packages;
    }
}
