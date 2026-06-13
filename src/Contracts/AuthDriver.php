<?php

namespace Meraki\Core\Contracts;

interface AuthDriver
{
    public function check(): bool;

    public function id(): mixed;

    public function user(): ?object;
}
