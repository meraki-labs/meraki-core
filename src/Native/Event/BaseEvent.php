<?php

namespace Meraki\Core\Native\Event;

use Meraki\Core\Contracts\Event\DomainEventInterface;

/**
 * @Author DatPA
 */
abstract class BaseEvent implements DomainEventInterface
{
    /**
     * @return array
     */
    public function payload(): array
    {
        return get_object_vars($this);
    }
}
