<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionContentVersion extends Model
{
    protected $fillable = ['status', 'version', 'content', 'created_by', 'published_by', 'published_at'];

    protected function casts(): array
    {
        return ['content' => 'array', 'published_at' => 'datetime'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(PageSection::class, 'page_section_id');
    }
}
