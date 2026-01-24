<?php

namespace Meraki\Core\Contracts\Event;

/**
 * @Author DatPA
 */
interface EventDispatcherInterface
{
    /**
     * @param string $event
     * @param mixed|null $payload
     * @return void
     */
    public function dispatch(string $event, mixed $payload = null): void;
}
