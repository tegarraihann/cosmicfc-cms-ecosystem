<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchAdminPresence extends Model
{
    protected $fillable = ['match_id', 'user_id', 'area', 'last_seen_at'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime'];
    }
}
