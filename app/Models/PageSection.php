<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PageSection extends Model
{
    protected $fillable = ['section_key', 'label', 'component', 'is_locked', 'is_visible', 'sort_order', 'draft_visible', 'draft_sort_order', 'settings'];

    protected function casts(): array
    {
        return ['is_locked' => 'boolean', 'is_visible' => 'boolean', 'draft_visible' => 'boolean', 'settings' => 'array'];
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(PageLayout::class, 'page_layout_id');
    }

    public function contentVersions(): HasMany
    {
        return $this->hasMany(SectionContentVersion::class);
    }

    public function draftContent(): HasOne
    {
        return $this->hasOne(SectionContentVersion::class)->where('status', 'draft');
    }

    public function publishedContent(): HasOne
    {
        return $this->hasOne(SectionContentVersion::class)->where('status', 'published');
    }
}
