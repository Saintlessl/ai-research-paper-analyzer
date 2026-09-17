<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperComparison extends Model
{
    protected $fillable = [
        'paper_a_id',
        'paper_b_id',
        'user_id',
        'dimensions',
        'conclusion',
        'reasoning',
        'evidence',
        'status',
        'request_id',
    ];

    protected function casts(): array
    {
        return [
            'dimensions' => 'array',
            'evidence' => 'array',
        ];
    }

    public function paperA(): BelongsTo
    {
        return $this->belongsTo(Paper::class, 'paper_a_id');
    }

    public function paperB(): BelongsTo
    {
        return $this->belongsTo(Paper::class, 'paper_b_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
