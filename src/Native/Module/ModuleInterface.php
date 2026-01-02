<?php

namespace Meraki\Core\Native\Module;

interface ModuleInterface
{
    public function name(): string;

    public function isEnabled(): bool;
}