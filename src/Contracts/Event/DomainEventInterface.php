<?php

namespace Meraki\Core\Contracts\Event;

interface DomainEventInterface
{
    public function name(): string;

    public function payload(): array;
}
