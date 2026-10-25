<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsArticle extends Model
{
    protected $fillable = ['uuid', 'slug', 'type', 'category_id', 'match_id', 'cover_media_id', 'title', 'excerpt', 'body', 'seo_title', 'seo_description', 'status', 'is_featured', 'published_at', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'published_at' => 'datetime'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'category_id');
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function gallery(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'news_article_media')->withPivot(['caption', 'display_order'])->orderByPivot('display_order');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(NewsArticleAudit::class);
    }
}
