<?php

namespace Tests\Feature;

use App\Models\Paper;
use App\Models\PaperComparison;
use App\Models\PaperQuestion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DomainSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_and_operational_contract_fields_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('papers', ['original_filename', 'mime_type', 'file_size']));
        $this->assertTrue(Schema::hasColumns('paper_authors', ['email', 'orcid', 'author_order']));
        $this->assertTrue(Schema::hasColumns('paper_analyses', ['research_problem', 'research_questions', 'research_objective', 'hypothesis', 'dataset', 'sample_size', 'key_findings', 'limitations', 'strengths', 'weaknesses', 'raw_output']));
        $this->assertTrue(Schema::hasColumns('paper_references', ['title', 'authors', 'doi', 'url', 'citation_count', 'issues']));
        $this->assertTrue(Schema::hasColumns('reviewer_assignments', ['status', 'accepted_at', 'completed_at']));
        $this->assertTrue(Schema::hasColumns('reviews', ['strengths', 'major_concerns', 'minor_concerns', 'methodology_review', 'novelty_review', 'results_review', 'reproducibility_review', 'recommendation_reason', 'evidence']));
        $this->assertTrue(Schema::hasColumns('ai_jobs', ['user_id', 'max_retries', 'duration_ms', 'error_code', 'error_message']));
        $this->assertTrue(Schema::hasColumn('ai_responses', 'validation_status'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'user_agent'));
        $this->assertTrue(Schema::hasTable('paper_questions'));
        $this->assertTrue(Schema::hasTable('paper_comparisons'));
    }

    public function test_scores_are_constrained_to_zero_through_one_hundred(): void
    {
        $user = User::factory()->create();
        $paper = Paper::create(['title' => 'Test', 'status' => 'UPLOADED', 'uploaded_by' => $user->id, 'file_path' => 'papers/test.pdf']);
        $analysis = $paper->analysis()->create();

        $this->expectException(QueryException::class);
        DB::table('paper_scores')->insert(['paper_analysis_id' => $analysis->id, 'criterion' => 'clarity', 'score' => 101, 'reason' => 'invalid', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_scores_reject_negative_values(): void
    {
        $user = User::factory()->create();
        $paper = Paper::create(['title' => 'Test', 'status' => 'UPLOADED', 'uploaded_by' => $user->id, 'file_path' => 'papers/test.pdf']);
        $analysis = $paper->analysis()->create();

        $this->expectException(QueryException::class);
        DB::table('paper_scores')->insert(['paper_analysis_id' => $analysis->id, 'criterion' => 'evidence', 'score' => -1, 'reason' => 'invalid', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_scores_accept_inclusive_boundaries(): void
    {
        $user = User::factory()->create();
        $paper = Paper::create(['title' => 'Test', 'status' => 'UPLOADED', 'uploaded_by' => $user->id, 'file_path' => 'papers/test.pdf']);
        $analysis = $paper->analysis()->create();

        $analysis->scores()->create(['criterion' => 'methodology', 'score' => 0, 'reason' => 'lower boundary']);
        $analysis->scores()->create(['criterion' => 'clarity', 'score' => 100, 'reason' => 'upper boundary']);

        $this->assertSame([0, 100], $analysis->scores()->orderBy('score')->pluck('score')->all());
    }

    public function test_scores_must_be_whole_numbers(): void
    {
        $user = User::factory()->create();
        $paper = Paper::create(['title' => 'Test', 'status' => 'UPLOADED', 'uploaded_by' => $user->id, 'file_path' => 'papers/test.pdf']);
        $analysis = $paper->analysis()->create();

        $this->expectException(QueryException::class);
        DB::table('paper_scores')->insert(['paper_analysis_id' => $analysis->id, 'criterion' => 'novelty', 'score' => 50.5, 'reason' => 'invalid', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_score_updates_must_remain_whole_numbers(): void
    {
        $user = User::factory()->create();
        $paper = Paper::create(['title' => 'Test', 'status' => 'UPLOADED', 'uploaded_by' => $user->id, 'file_path' => 'papers/test.pdf']);
        $analysis = $paper->analysis()->create();
        $score = $analysis->scores()->create(['criterion' => 'novelty', 'score' => 50, 'reason' => 'valid']);

        $this->expectException(QueryException::class);
        DB::table('paper_scores')->where('id', $score->id)->update(['score' => 50.5]);
    }

    public function test_questions_and_comparisons_are_persistable(): void
    {
        $user = User::factory()->create();
        $a = Paper::create(['title' => 'A', 'status' => 'UPLOADED', 'uploaded_by' => $user->id, 'file_path' => 'papers/a.pdf']);
        $b = Paper::create(['title' => 'B', 'status' => 'UPLOADED', 'uploaded_by' => $user->id, 'file_path' => 'papers/b.pdf']);

        $question = PaperQuestion::create(['paper_id' => $a->id, 'user_id' => $user->id, 'question' => 'Dataset?', 'status' => 'PENDING']);
        $comparison = PaperComparison::create(['paper_a_id' => $a->id, 'paper_b_id' => $b->id, 'user_id' => $user->id, 'status' => 'PENDING']);

        $this->assertSame($a->id, $question->paper->id);
        $this->assertSame($user->id, $question->user->id);
        $this->assertSame($a->id, $comparison->paperA->id);
        $this->assertSame($b->id, $comparison->paperB->id);
        $this->assertSame($user->id, $comparison->user->id);
        $this->assertTrue($a->questions->contains($question));
        $this->assertTrue($a->comparisonsAsPaperA->contains($comparison));
    }

    public function test_structured_academic_fields_are_cast_to_native_values(): void
    {
        $user = User::factory()->create();
        $paper = Paper::create([
            'title' => 'Structured paper',
            'status' => 'ANALYZED',
            'uploaded_by' => $user->id,
            'file_path' => 'papers/structured.pdf',
            'keywords' => ['machine learning'],
            'file_size' => 1024,
        ]);

        $analysis = $paper->analysis()->create([
            'research_questions' => ['How accurate is the model?'],
            'key_findings' => ['The model improved accuracy.'],
            'limitations' => ['Single dataset.'],
            'raw_output' => ['validated' => true],
        ]);

        $this->assertSame(['machine learning'], $paper->fresh()->keywords);
        $this->assertSame(['How accurate is the model?'], $analysis->fresh()->research_questions);
        $this->assertSame(['The model improved accuracy.'], $analysis->fresh()->key_findings);
        $this->assertSame(['Single dataset.'], $analysis->fresh()->limitations);
        $this->assertTrue($analysis->fresh()->raw_output['validated']);
    }
}
