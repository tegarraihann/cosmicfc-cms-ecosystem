<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemProcessRun extends Model
{
    protected $fillable = ['process', 'status', 'started_at', 'finished_at', 'summary', 'error_summary'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime', 'summary' => 'array'];
    }
}
