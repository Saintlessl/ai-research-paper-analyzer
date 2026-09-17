<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaperAnalysis extends Model
{
    protected $fillable = [
        'paper_id',
        'paper_type',
        'research_domain',
        'summary',
        'research_problem',
        'research_questions',
        'research_objective',
        'hypothesis',
        'structure',
        'methodology',
        'dataset',
        'sample_size',
        'key_findings',
        'limitations',
        'strengths',
        'weaknesses',
        'keywords',
        'raw_output',
    ];

    protected function casts(): array
    {
        return [
            'research_questions' => 'array',
            'structure' => 'array',
            'methodology' => 'array',
            'key_findings' => 'array',
            'limitations' => 'array',
            'strengths' => 'array',
            'weaknesses' => 'array',
            'keywords' => 'array',
            'raw_output' => 'array',
            'sample_size' => 'integer',
        ];
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PaperScore::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(PaperFinding::class);
    }
}
