<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AwardType extends Model
{
    protected $fillable = ['name', 'slug', 'period_type', 'description', 'icon', 'is_active', 'display_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function awards(): HasMany
    {
        return $this->hasMany(PlayerAward::class);
    }
}
