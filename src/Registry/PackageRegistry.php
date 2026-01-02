<?php

namespace Meraki\Core\Registry;

class PackageRegistry
{
    protected static array $packages = [];

    public static function register(string $name, array $meta = []): void
    {
        self::$packages[$name] = $meta;
    }

    public static function all(): array
    {
        return self::$packages;
    }
}
