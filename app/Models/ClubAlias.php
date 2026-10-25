<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubAlias extends Model
{
    protected $fillable = ['club_id', 'alias', 'normalized_alias', 'source'];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
}
