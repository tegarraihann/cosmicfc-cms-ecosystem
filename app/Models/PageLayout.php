<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageLayout extends Model
{
    protected $fillable = ['page_key', 'label', 'path', 'version', 'has_unpublished_changes', 'published_at', 'published_by'];

    protected function casts(): array
    {
        return ['has_unpublished_changes' => 'boolean', 'published_at' => 'datetime'];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PageLayoutAudit::class);
    }
}
