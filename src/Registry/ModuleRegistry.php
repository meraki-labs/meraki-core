<?php

namespace Meraki\Core\Registry;

use Meraki\Core\Native\Module\ModuleInterface;

class ModuleRegistry
{
    protected static array $modules = [];

    public static function register(ModuleInterface $module): void
    {
        self::$modules[$module->name()] = $module;
    }

    public static function all(): array
    {
        return self::$modules;
    }
}
