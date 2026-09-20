<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperFinding extends Model
{
    protected $fillable = [
        'paper_analysis_id',
        'kind',
        'severity',
        'category',
        'finding',
        'explanation',
        'confidence',
        'evidence',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'evidence' => 'array',
        ];
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(PaperAnalysis::class, 'paper_analysis_id');
    }
}
