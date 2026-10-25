<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClubAudit extends Model
{
    protected $fillable = ['club_id', 'user_id', 'action', 'before_payload', 'after_payload'];

    protected function casts(): array
    {
        return ['before_payload' => 'array', 'after_payload' => 'array'];
    }
}
