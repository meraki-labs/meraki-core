<?php

namespace Meraki\Core\Contracts\Event;

/**
 * @Author DatPA
 */
interface DomainEventInterface
{
    public function name(): string;

    public function payload(): array;
}
