<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiResponse extends Model
{
    protected $fillable = [
        'ai_request_id',
        'success',
        'payload',
        'error_code',
        'error_message',
        'duration_ms',
        'validation_status',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'payload' => 'array',
            'duration_ms' => 'integer',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AiRequest::class, 'ai_request_id');
    }
}
