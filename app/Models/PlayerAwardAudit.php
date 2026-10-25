<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerAwardAudit extends Model
{
    public $timestamps = false;

    protected $fillable = ['player_award_id', 'user_id', 'action', 'before_payload', 'after_payload', 'correction_reason', 'created_at'];

    protected function casts(): array
    {
        return ['before_payload' => 'array', 'after_payload' => 'array', 'created_at' => 'datetime'];
    }
}
