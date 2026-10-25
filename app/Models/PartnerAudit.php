<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerAudit extends Model
{
    public $timestamps = false;
    protected $fillable = ['sponsor_id', 'partnership_id', 'user_id', 'action', 'before_payload', 'after_payload', 'created_at'];
    protected function casts(): array { return ['before_payload' => 'array', 'after_payload' => 'array', 'created_at' => 'datetime']; }
}
