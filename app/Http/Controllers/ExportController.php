<?php

namespace App\Http\Controllers;

use App\Models\Paper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function show(Request $request, Paper $paper): StreamedResponse
    {
        Gate::authorize('view', $paper);

        $paper->load([
            'authors',
            'analysis.scores',
            'analysis.findings',
            'references',
            'reviews.reviewer',
            'reviews.comments.user',
        ]);

        $report = [
            'paper' => [
                'id' => $paper->id,
                'title' => $paper->title,
                'abstract' => $paper->abstract,
                'authors' => $paper->authors->map(fn ($a) => $a->name)->toArray(),
                'publication_year' => $paper->publication_year,
                'journal' => $paper->journal,
                'doi' => $paper->doi,
                'keywords' => $paper->keywords,
                'status' => $paper->status,
            ],
            'analysis' => $paper->analysis ? collect($paper->analysis->toArray())
                ->except(['id', 'paper_id', 'created_at', 'updated_at', 'raw_output'])
                ->filter()
                ->all() : null,
            'scores' => $paper->analysis?->scores->map(fn ($s) => [
                'criterion' => $s->criterion,
                'score' => $s->score,
                'reason' => $s->reason,
            ])->toArray() ?? [],
            'findings' => $paper->analysis?->findings->map(fn ($f) => [
                'kind' => $f->kind,
                'severity' => $f->severity,
                'category' => $f->category,
                'finding' => $f->finding,
                'explanation' => $f->explanation,
            ])->toArray() ?? [],
            'references' => $paper->references->map(fn ($r) => [
                'citation' => $r->citation,
                'cited_in_text' => $r->cited_in_text,
            ])->toArray(),
            'reviews' => $paper->reviews->map(fn ($r) => [
                'reviewer' => $r->reviewer?->name,
                'recommendation' => $r->recommendation,
                'summary' => $r->summary,
                'comments' => $r->comments->map(fn ($c) => [
                    'user' => $c->user?->name,
                    'body' => $c->body,
                    'page' => $c->page,
                ])->toArray(),
            ])->toArray(),
            'exported_at' => now()->toIso8601String(),
        ];

        $filename = 'report-' . \Illuminate\Support\Str::slug($paper->title) . '.json';

        return response()->streamDownload(function () use ($report) {
            echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }
}
