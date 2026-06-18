<?php

namespace Meraki\Core\Plugin;

use Illuminate\Support\Facades\DB;

class DatabaseStateStore implements PluginStateStore
{
    private function key(string $type, string $name): string
    {
        return "plugin.{$type}.{$name}";
    }

    public function isEnabled(string $name): bool
    {
        if (in_array($name, config('meraki.plugins.disabled', []))) {
            return false;
        }

        $val = DB::table('meraki_meta')
            ->where('key', $this->key('enabled', $name))
            ->value('value');

        return $val === null ? true : (bool) $val;
    }

    public function enable(string $name): void
    {
        DB::table('meraki_meta')->updateOrInsert(
            ['key' => $this->key('enabled', $name)],
            ['value' => '1', 'updated_at' => now()]
        );
    }

    public function disable(string $name): void
    {
        DB::table('meraki_meta')->updateOrInsert(
            ['key' => $this->key('enabled', $name)],
            ['value' => '0', 'updated_at' => now()]
        );
    }

    public function isInstalled(string $name): bool
    {
        return (bool) DB::table('meraki_meta')
            ->where('key', $this->key('installed', $name))
            ->value('value');
    }

    public function markInstalled(string $name): void
    {
        DB::table('meraki_meta')->updateOrInsert(
            ['key' => $this->key('installed', $name)],
            ['value' => '1', 'updated_at' => now()]
        );
    }

    public function markUninstalled(string $name): void
    {
        DB::table('meraki_meta')->updateOrInsert(
            ['key' => $this->key('installed', $name)],
            ['value' => '0', 'updated_at' => now()]
        );
    }
}
