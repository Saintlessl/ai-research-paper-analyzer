<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    protected $fillable = [
        'paper_id',
        'reviewer_id',
        'status',
        'recommendation',
        'recommendation_reason',
        'summary',
        'strengths',
        'major_concerns',
        'minor_concerns',
        'methodology_review',
        'novelty_review',
        'results_review',
        'reproducibility_review',
        'scores',
        'evidence',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'strengths' => 'array',
            'major_concerns' => 'array',
            'minor_concerns' => 'array',
            'scores' => 'array',
            'evidence' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReviewComment::class);
    }
}
