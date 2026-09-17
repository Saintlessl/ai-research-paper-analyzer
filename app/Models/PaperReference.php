<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperReference extends Model
{
    protected $fillable = [
        'paper_id',
        'raw_text',
        'citation_key',
        'title',
        'authors',
        'publication_year',
        'doi',
        'url',
        'citation_count',
        'cited_in_text',
        'issues',
    ];

    protected function casts(): array
    {
        return [
            'authors' => 'array',
            'issues' => 'array',
            'publication_year' => 'integer',
            'citation_count' => 'integer',
            'cited_in_text' => 'boolean',
        ];
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }
}
