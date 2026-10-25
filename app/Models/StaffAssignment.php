<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAssignment extends Model
{
    protected $fillable = ['staff_id', 'role_title', 'category', 'short_description', 'badge', 'starts_at', 'ends_at', 'is_primary_founder', 'is_featured', 'featured_order', 'is_public', 'status'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'is_primary_founder' => 'boolean', 'is_featured' => 'boolean', 'is_public' => 'boolean'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
