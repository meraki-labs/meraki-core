<?php

namespace Meraki\Core;

use Closure;
use InvalidArgumentException;
use Illuminate\Contracts\Foundation\Application;
use Meraki\Core\Contracts\AuthDriver;
use Meraki\Core\Contracts\PermissionDriver;
use Meraki\Core\Hooks\HookRegistry;
use Meraki\Core\Modules\PackageRegistry;

class CoreManager
{
    protected array $factories = [];
    protected array $resolved = [];

    public function __construct(
        protected Application $app,
        protected PackageRegistry $packageRegistry,
        protected HookRegistry $hookRegistry,
    ) {}

    public function extend(string $capability, string $name, Closure $factory): void
    {
        $this->factories[$capability][$name] = $factory;
        unset($this->resolved[$capability]);
    }

    public function capability(string $capability): object
    {
        if (isset($this->resolved[$capability])) {
            return $this->resolved[$capability];
        }

        $driverName = config("meraki.capabilities.{$capability}.driver", 'auto');

        if ($driverName !== 'auto') {
            if (!isset($this->factories[$capability][$driverName])) {
                $available = implode(', ', array_keys($this->factories[$capability] ?? []));
                throw new InvalidArgumentException(
                    "Driver [{$driverName}] for capability [{$capability}] not found. Available: [{$available}]."
                );
            }
            return $this->resolved[$capability] = ($this->factories[$capability][$driverName])($this->app);
        }

        // auto: prefer last registered package driver, fallback to Laravel adapter
        if (!empty($this->factories[$capability])) {
            $factory = end($this->factories[$capability]);
            return $this->resolved[$capability] = $factory($this->app);
        }

        return $this->resolved[$capability] = $this->app->make(
            $this->defaultDriver($capability)
        );
    }

    public function auth(): AuthDriver
    {
        return $this->capability('auth');
    }

    public function permission(): PermissionDriver
    {
        return $this->capability('permission');
    }

    public function can(string $permission, mixed $user = null): bool
    {
        return $this->permission()->can($permission, $user);
    }

    public function packages(): PackageRegistry
    {
        return $this->packageRegistry;
    }

    public function hooks(): HookRegistry
    {
        return $this->hookRegistry;
    }

    protected function defaultDriver(string $capability): string
    {
        return match ($capability) {
            'auth'       => \Meraki\Core\Adapters\LaravelAuthAdapter::class,
            'permission' => \Meraki\Core\Adapters\LaravelGateAdapter::class,
            default      => throw new InvalidArgumentException("No default driver for capability [{$capability}]."),
        };
    }
}
