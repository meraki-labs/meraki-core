<?php

namespace Meraki\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Meraki\Core\Database\Factories\PluginFactory;

class Plugin extends Model
{
    use HasFactory;

    protected $table = 'meraki_plugins';

    protected $fillable = ['name', 'version', 'status', 'installed_at', 'meta'];

    protected $casts = [
        'meta'         => 'array',
        'installed_at' => 'datetime',
    ];

    const STATUS_ACTIVE   = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_FAILED   = 'failed';

    protected static function newFactory(): PluginFactory
    {
        return PluginFactory::new();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
