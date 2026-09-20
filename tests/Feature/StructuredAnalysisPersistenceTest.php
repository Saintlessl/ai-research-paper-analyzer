<?php

namespace Tests\Feature;

use App\Models\Paper;
use App\Models\User;
use App\Services\AnalysisPersister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StructuredAnalysisPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_validated_analysis_is_replaced_transactionally_in_normalized_tables(): void
    {
        $paper = $this->paper();
        $persister = app(AnalysisPersister::class);

        $persister->persist($paper, $this->payload());
        $changed = $this->payload();
        $changed['classification']['paper_type'] = 'observational';
        $persister->persist($paper, $changed);

        $analysis = $paper->fresh()->analysis;
        $this->assertSame('observational', $analysis->paper_type);
        $this->assertSame('Treatment effectiveness', $analysis->research_problem);
        $this->assertNull($analysis->hypothesis);
        $this->assertCount(7, $analysis->scores);
        $this->assertCount(4, $analysis->findings);
        $this->assertSame('chunk-0002', $analysis->scores->first()->evidence[0]['chunk_id']);
        $this->assertSame('finding', $analysis->findings->firstWhere('category', 'results')->kind);
    }

    public function test_invalid_payload_rolls_back_without_replacing_existing_analysis(): void
    {
        $paper = $this->paper();
        $persister = app(AnalysisPersister::class);
        $persister->persist($paper, $this->payload());
        $invalid = $this->payload();
        $invalid['scores'][0]['score'] = 101;

        try {
            $persister->persist($paper, $invalid);
            $this->fail('Invalid analysis accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('paper_analyses', 1);
            $this->assertDatabaseCount('paper_scores', 7);
            $this->assertDatabaseCount('paper_findings', 4);
        }
    }

    private function paper(): Paper
    {
        $user = User::factory()->create();
        return Paper::query()->create([
            'title' => 'Paper', 'file_path' => 'papers/test.pdf', 'status' => 'UPLOADED', 'uploaded_by' => $user->id,
        ]);
    }

    private function payload(): array
    {
        $evidence = [['page' => 2, 'section' => 'Methods', 'chunk_id' => 'chunk-0002', 'excerpt' => 'We enrolled 120 participants.', 'confidence' => .91]];
        $criteria = ['clarity', 'methodological_rigor', 'novelty', 'validity', 'reproducibility', 'significance', 'evidence_quality'];
        $item = fn (string $kind, string $category, string $finding) => compact('kind', 'category', 'finding') + ['severity' => 'MEDIUM', 'explanation' => null, 'confidence' => .8, 'evidence' => $evidence];
        return [
            'classification' => ['paper_type' => 'experimental', 'research_domain' => 'medicine', 'reason' => 'Controlled experiment.', 'evidence' => $evidence],
            'structure' => ['summary' => 'Empirical structure.', 'sections' => [], 'evidence' => $evidence],
            'methodology' => ['research_problem' => 'Treatment effectiveness', 'research_questions' => ['Does it work?'], 'research_objective' => 'Estimate effect.', 'hypothesis' => null, 'study_design' => 'RCT', 'methods' => ['Random allocation'], 'dataset' => 'Trial cohort', 'sample_size' => 120, 'evidence' => $evidence],
            'scores' => array_map(fn ($criterion) => compact('criterion') + ['score' => 80, 'reason' => 'Grounded reason.', 'evidence' => $evidence], $criteria),
            'findings' => [$item('finding', 'results', 'Treatment improved outcome.')],
            'limitations' => [$item('limitation', 'sampling', 'Single site.')],
            'strengths' => [$item('strength', 'design', 'Randomized.')],
            'weaknesses' => [$item('weakness', 'generalisability', 'Limited setting.')],
            'keywords' => ['trial'],
        ];
    }
}
