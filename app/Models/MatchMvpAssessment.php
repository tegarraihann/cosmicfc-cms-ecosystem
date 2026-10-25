<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchMvpAssessment extends Model
{
    protected $fillable = [
        'match_id', 'player_id', 'evaluator_id', 'result_impact',
        'position_performance', 'consistency', 'crucial_moments',
        'discipline_teamwork', 'total_score', 'evidence_snapshot',
        'assessment_note', 'assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_score' => 'decimal:2',
            'evidence_snapshot' => 'array',
            'assessed_at' => 'datetime',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
