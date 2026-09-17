<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperAuthor extends Model
{
    protected $fillable = [
        'paper_id',
        'name',
        'email',
        'affiliation',
        'orcid',
        'author_order',
    ];

    protected function casts(): array
    {
        return ['author_order' => 'integer'];
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }
}
