<?php

namespace Meraki\Core\Native\Module;

/**
 * @Author DatPA
 */
interface ModuleInterface
{
    /**
     * @return string
     */
    public function name(): string;

    /**
     * @return bool
     */
    public function isEnabled(): bool;
}