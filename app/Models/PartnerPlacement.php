<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerPlacement extends Model
{
    protected $fillable = ['partnership_id', 'placement_key', 'display_order', 'is_visible'];
    protected function casts(): array { return ['is_visible' => 'boolean']; }
    public function partnership(): BelongsTo { return $this->belongsTo(Partnership::class); }
}
