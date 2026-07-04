<?php

namespace Meraki\Core\Installer;

final class WizardSession
{
    private const KEY = '_meraki_wizard';

    public function get(string $field, mixed $default = null): mixed
    {
        return session(self::KEY . '.' . $field, $default);
    }

    public function set(string $field, mixed $value): void
    {
        session([self::KEY . '.' . $field => $value]);
    }

    /** @param mixed $value */
    public function push(string $field, mixed $value): void
    {
        $current = $this->get($field, []);
        if (!in_array($value, $current, true)) {
            $current[] = $value;
        }
        $this->set($field, $current);
    }

    public function has(string $field): bool
    {
        return session()->has(self::KEY . '.' . $field);
    }

    public function markStep(string $step): void
    {
        $this->push('steps_completed', $step);
    }

    public function stepCompleted(string $step): bool
    {
        return in_array($step, $this->get('steps_completed', []), true);
    }

    public function all(): array
    {
        return session(self::KEY, []);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }
}
