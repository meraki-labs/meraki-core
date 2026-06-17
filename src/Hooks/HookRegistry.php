<?php

namespace Meraki\Core\Hooks;

class HookRegistry
{
    /** @var array<string, array<int, list<callable>>> */
    protected array $hooks = [];

    public function add(string $hook, callable $callback, int $priority = 10): void
    {
        $this->hooks[$hook][$priority][] = $callback;
    }

    public function fire(string $hook, mixed ...$args): void
    {
        if (!isset($this->hooks[$hook])) {
            return;
        }

        ksort($this->hooks[$hook]);

        foreach ($this->hooks[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    public function has(string $hook): bool
    {
        if (!isset($this->hooks[$hook])) {
            return false;
        }

        foreach ($this->hooks[$hook] as $callbacks) {
            if (!empty($callbacks)) {
                return true;
            }
        }

        return false;
    }

    public function remove(string $hook, callable $callback): void
    {
        if (!isset($this->hooks[$hook])) {
            return;
        }

        foreach ($this->hooks[$hook] as $priority => $callbacks) {
            $this->hooks[$hook][$priority] = array_filter(
                $callbacks,
                fn ($cb) => $cb !== $callback
            );
        }
    }
}
