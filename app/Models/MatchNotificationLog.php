<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchNotificationLog extends Model
{
    protected $fillable = ['match_id', 'channel', 'notification_type', 'recipient', 'status', 'idempotency_key', 'sent_at', 'failed_at', 'error_summary'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'failed_at' => 'datetime'];
    }
}
