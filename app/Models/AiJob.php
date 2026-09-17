<?php

namespace App\Models;

use App\Enums\AiJobStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiJob extends Model
{
    protected $fillable = [
        'request_id',
        'paper_id',
        'user_id',
        'type',
        'status',
        'retry_count',
        'max_retries',
        'error',
        'error_code',
        'error_message',
        'duration_ms',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AiJobStatus::class,
            'retry_count' => 'integer',
            'max_retries' => 'integer',
            'duration_ms' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function requests(): HasMany
    {
        return $this->hasMany(AiRequest::class);
    }
}
