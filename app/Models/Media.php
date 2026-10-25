<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use SoftDeletes;

    protected $table = 'media';

    protected $fillable = ['uuid', 'disk', 'directory', 'filename', 'original_name', 'extension', 'mime_type', 'size_bytes', 'width', 'height', 'checksum', 'title', 'alt_text', 'caption', 'folder', 'status', 'uploaded_by', 'deleted_by'];

    protected $appends = ['url'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MediaTag::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(MediaAudit::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk($this->disk)->url("{$this->directory}/{$this->filename}"));
    }
}
