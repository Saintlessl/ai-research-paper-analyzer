<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperScore extends Model
{
    protected $fillable = [
        'paper_analysis_id',
        'criterion',
        'score',
        'reason',
        'evidence',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'evidence' => 'array',
        ];
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(PaperAnalysis::class, 'paper_analysis_id');
    }
}
