<?php

namespace App\Models;

use App\Enums\PaperStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paper extends Model
{
    protected $fillable = [
        'title',
        'abstract',
        'publication_year',
        'journal',
        'doi',
        'keywords',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'status',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'publication_year' => 'integer',
            'file_size' => 'integer',
            'status' => PaperStatus::class,
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function authors(): HasMany
    {
        return $this->hasMany(PaperAuthor::class)->orderBy('author_order');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PaperSection::class);
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(PaperAnalysis::class);
    }

    public function references(): HasMany
    {
        return $this->hasMany(PaperReference::class);
    }

    public function aiJobs(): HasMany
    {
        return $this->hasMany(AiJob::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ReviewerAssignment::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(PaperQuestion::class);
    }

    public function comparisonsAsPaperA(): HasMany
    {
        return $this->hasMany(PaperComparison::class, 'paper_a_id');
    }

    public function comparisonsAsPaperB(): HasMany
    {
        return $this->hasMany(PaperComparison::class, 'paper_b_id');
    }
}
