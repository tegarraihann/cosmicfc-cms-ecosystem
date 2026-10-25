<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerAudit extends Model
{
    protected $fillable = ['player_id', 'user_id', 'action', 'before_payload', 'after_payload'];

    protected function casts(): array
    {
        return ['before_payload' => 'array', 'after_payload' => 'array'];
    }
}
