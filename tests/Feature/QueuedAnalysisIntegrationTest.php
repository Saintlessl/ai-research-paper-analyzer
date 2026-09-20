<?php

namespace Tests\Feature;

use App\Enums\AiJobStatus;
use App\Jobs\ProcessPaper;
use App\Models\AiJob;
use App\Models\Paper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QueuedAnalysisIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_reads_private_pdf_calls_ai_and_completes_only_after_persistence(): void
    {
        Storage::fake('paper-files');
        config()->set('services.ai', ['url' => 'http://ai.test', 'token' => 'secret', 'timeout' => 15]);
        Http::fake(['http://ai.test/api/v1/analyze' => Http::response(['success' => true, 'data' => $this->payload()])]);
        $user = User::factory()->create();
        Storage::disk('paper-files')->put('papers/private.pdf', '%PDF-private');
        $paper = Paper::query()->create([
            'title' => 'Private paper', 'file_path' => 'papers/private.pdf', 'storage_disk' => 'paper-files',
            'original_filename' => 'original.pdf', 'mime_type' => 'application/pdf', 'status' => 'UPLOADED', 'uploaded_by' => $user->id,
        ]);
        $jobRecord = $paper->aiJobs()->create([
            'request_id' => '123e4567-e89b-12d3-a456-426614174000', 'user_id' => $user->id,
            'type' => 'paper.analysis', 'status' => AiJobStatus::Pending, 'max_retries' => 3,
        ]);

        app()->call([new ProcessPaper($jobRecord->id), 'handle']);

        $this->assertSame(AiJobStatus::Completed, $jobRecord->fresh()->status);
        $this->assertSame('experimental', $paper->fresh()->analysis->paper_type);
        $this->assertDatabaseCount('paper_scores', 7);
        Http::assertSent(fn ($request) => str_contains($request->body(), '%PDF-private'));
    }

    private function payload(): array
    {
        $criteria = ['clarity', 'methodological_rigor', 'novelty', 'validity', 'reproducibility', 'significance', 'evidence_quality'];
        return [
            'classification' => ['paper_type' => 'experimental', 'research_domain' => 'medicine', 'reason' => null, 'evidence' => []],
            'structure' => ['summary' => 'Summary', 'sections' => [], 'evidence' => []],
            'methodology' => ['research_problem' => null, 'research_questions' => null, 'research_objective' => null, 'hypothesis' => null, 'study_design' => null, 'methods' => null, 'dataset' => null, 'sample_size' => null, 'evidence' => []],
            'scores' => array_map(fn ($criterion) => compact('criterion') + ['score' => 70, 'reason' => 'Grounded', 'evidence' => []], $criteria),
            'findings' => [], 'limitations' => [], 'strengths' => [], 'weaknesses' => [], 'keywords' => [],
            'ai_suspected_citation_findings' => [],
            'citation_analysis' => [
                'total_references' => 0, 'publication_years' => [], 'recent_year_cutoff' => 2021,
                'recent_count' => 0, 'older_count' => 0,
                'citation_patterns' => ['numeric_bracket' => 0, 'author_year' => 0],
                'in_text_citations_missing_from_bibliography' => [],
                'bibliography_entries_apparently_uncited' => [],
                'potentially_irrelevant_patterns' => [], 'references' => [],
                'method' => 'deterministic_heuristic',
            ],
        ];
    }
}