<?php

namespace Meraki\Core\Contracts;

use Illuminate\Contracts\Foundation\Application;

interface Plugin
{
    public function id(): string;
    public function name(): string;
    public function version(): string;
    public function description(): string;
    public function register(Application $app): void;
    public function boot(Application $app): void;
}
