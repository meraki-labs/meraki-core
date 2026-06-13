<?php

namespace Meraki\Core\Adapters;

use Meraki\Core\Contracts\AuthDriver;
use Illuminate\Support\Facades\Auth;

class LaravelAuthAdapter implements AuthDriver
{
    public function check(): bool
    {
        return Auth::check();
    }

    public function id(): mixed
    {
        return Auth::id();
    }

    public function user(): ?object
    {
        return Auth::user();
    }
}
