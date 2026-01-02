<?php

namespace Meraki\Core\Contracts\Auth;

interface AuthInterface
{
    public function id(): mixed;

    public function check(): bool;

    public function user(): mixed;
}
