<?php

namespace Meraki\Core\Registry;

use Meraki\Core\Native\Module\ModuleInterface;

/**
 * @Author DatPA
 */
class ModuleRegistry
{
    protected static array $modules = [];

    /**
     * @param ModuleInterface $module
     * @return void
     */
    public static function register(ModuleInterface $module): void
    {
        self::$modules[$module->name()] = $module;
    }

    /**
     * @return array
     */
    public static function all(): array
    {
        return self::$modules;
    }
}
