<?php

namespace Meraki\Core\Event;

use Meraki\Core\Contracts\Event\DomainEventInterface;

abstract class BaseEvent implements DomainEventInterface
{
    public function payload(): array
    {
        return get_object_vars($this);
    }
}
