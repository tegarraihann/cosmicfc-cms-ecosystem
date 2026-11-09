<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsSetting extends Model
{
    protected $fillable = [
        'enabled', 'measurement_id', 'property_id', 'service_account',
        'cache_minutes', 'connection_status', 'last_error', 'last_tested_at',
        'last_synced_at', 'updated_by',
    ];

    protected $hidden = ['service_account'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'service_account' => 'encrypted:array',
            'last_tested_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], ['cache_minutes' => 30]);
    }
}
