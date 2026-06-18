<?php

namespace Meraki\Core\Plugins;

use Illuminate\Support\Facades\DB;

class PluginRepository
{
    public function isEnabled(string $id): bool
    {
        $record = DB::table('meraki_plugins')->where('id', $id)->first();

        return $record ? (bool) $record->enabled : false;
    }

    public function setEnabled(string $id, bool $value, ?string $version = null): void
    {
        $now = now();

        DB::table('meraki_plugins')->upsert(
            [
                'id'         => $id,
                'enabled'    => $value,
                'version'    => $version,
                'enabled_at' => $value ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['id'],
            ['enabled', 'version', 'enabled_at', 'updated_at'],
        );
    }

    public function all(): array
    {
        return DB::table('meraki_plugins')->get()->all();
    }
}
