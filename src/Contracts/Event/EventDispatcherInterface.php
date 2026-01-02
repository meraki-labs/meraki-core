<?php

namespace Meraki\Core\Contracts\Event;

interface EventDispatcherInterface
{
    public function dispatch(string $event, mixed $payload = null): void;
}
