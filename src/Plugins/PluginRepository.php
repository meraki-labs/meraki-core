<?php

namespace Meraki\Core\Plugins;

use Illuminate\Support\Facades\DB;

class PluginRepository
{
    public function isEnabled(string $id): bool
    {
        $record = DB::table('meraki_plugins')->where('name', $id)->first();

        return $record ? (bool) $record->enabled : false;
    }

    public function setEnabled(string $id, bool $value, ?string $version = null): void
    {
        $now = now();

        DB::table('meraki_plugins')->upsert(
            [
                'name'       => $id,
                'enabled'    => $value,
                'version'    => $version,
                'enabled_at' => $value ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['name'],
            ['enabled', 'version', 'enabled_at', 'updated_at'],
        );
    }

    public function markInstalled(string $id, string $version): void
    {
        $now = now();

        DB::table('meraki_plugins')->upsert(
            [
                'name'         => $id,
                'version'      => $version,
                'status'       => 'inactive',
                'enabled'      => false,
                'installed_at' => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            ['name'],
            ['version', 'status', 'installed_at', 'updated_at'],
        );
    }

    public function markUninstalled(string $id): void
    {
        DB::table('meraki_plugins')->where('name', $id)->delete();
    }

    public function isInstalled(string $id): bool
    {
        return DB::table('meraki_plugins')->where('name', $id)->exists();
    }

    public function all(): array
    {
        return DB::table('meraki_plugins')->get()->all();
    }
}
