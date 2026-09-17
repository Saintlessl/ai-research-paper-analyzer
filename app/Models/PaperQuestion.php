<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperQuestion extends Model
{
    protected $fillable = [
        'paper_id',
        'user_id',
        'question',
        'answer',
        'found',
        'evidence',
        'status',
        'request_id',
    ];

    protected function casts(): array
    {
        return [
            'found' => 'boolean',
            'evidence' => 'array',
        ];
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
