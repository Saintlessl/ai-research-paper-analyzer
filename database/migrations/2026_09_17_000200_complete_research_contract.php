<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SCORE_CONSTRAINT = 'paper_scores_score_check';

    public function up(): void
    {
        Schema::table('papers', function (Blueprint $table) {
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
        });

        Schema::table('paper_authors', function (Blueprint $table) {
            $table->renameColumn('position', 'author_order');
            $table->string('email')->nullable();
            $table->string('orcid')->nullable()->index();
        });

        Schema::table('paper_analyses', function (Blueprint $table) {
            $table->text('research_problem')->nullable();
            $table->json('research_questions')->nullable();
            $table->text('research_objective')->nullable();
            $table->text('hypothesis')->nullable();
            $table->text('dataset')->nullable();
            $table->unsignedBigInteger('sample_size')->nullable();
            $table->json('key_findings')->nullable();
            $table->json('limitations')->nullable();
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('raw_output')->nullable();
        });

        Schema::table('paper_references', function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->json('authors')->nullable();
            $table->string('doi')->nullable()->index();
            $table->string('url')->nullable();
            $table->unsignedInteger('citation_count')->default(0);
            $table->json('issues')->nullable();
        });

        Schema::table('reviewer_assignments', function (Blueprint $table) {
            $table->string('status')->default('PENDING')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->json('strengths')->nullable();
            $table->json('major_concerns')->nullable();
            $table->json('minor_concerns')->nullable();
            $table->text('methodology_review')->nullable();
            $table->text('novelty_review')->nullable();
            $table->text('results_review')->nullable();
            $table->text('reproducibility_review')->nullable();
            $table->text('recommendation_reason')->nullable();
            $table->json('evidence')->nullable();
        });

        Schema::table('ai_jobs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('max_retries')->default(3);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
        });

        Schema::table('ai_responses', function (Blueprint $table) {
            $table->string('validation_status')->default('PENDING')->index();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->text('user_agent')->nullable();
        });

        Schema::create('paper_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paper_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->boolean('found')->nullable();
            $table->json('evidence')->nullable();
            $table->string('status')->index();
            $table->uuid('request_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('paper_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paper_a_id')->constrained('papers')->cascadeOnDelete();
            $table->foreignId('paper_b_id')->constrained('papers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('dimensions')->nullable();
            $table->text('conclusion')->nullable();
            $table->text('reasoning')->nullable();
            $table->json('evidence')->nullable();
            $table->string('status')->index();
            $table->uuid('request_id')->nullable()->unique();
            $table->timestamps();
        });

        $this->addScoreConstraint();
    }

    public function down(): void
    {
        $this->dropScoreConstraint();

        Schema::dropIfExists('paper_comparisons');
        Schema::dropIfExists('paper_questions');

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('user_agent');
        });

        Schema::table('ai_responses', function (Blueprint $table) {
            $table->dropIndex(['validation_status']);
            $table->dropColumn('validation_status');
        });

        Schema::table('ai_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['max_retries', 'duration_ms', 'error_code', 'error_message']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn([
                'strengths',
                'major_concerns',
                'minor_concerns',
                'methodology_review',
                'novelty_review',
                'results_review',
                'reproducibility_review',
                'recommendation_reason',
                'evidence',
            ]);
        });

        Schema::table('reviewer_assignments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'accepted_at', 'completed_at']);
        });

        Schema::table('paper_references', function (Blueprint $table) {
            $table->dropIndex(['doi']);
            $table->dropColumn(['title', 'authors', 'doi', 'url', 'citation_count', 'issues']);
        });

        Schema::table('paper_analyses', function (Blueprint $table) {
            $table->dropColumn([
                'research_problem',
                'research_questions',
                'research_objective',
                'hypothesis',
                'dataset',
                'sample_size',
                'key_findings',
                'limitations',
                'strengths',
                'weaknesses',
                'raw_output',
            ]);
        });

        Schema::table('paper_authors', function (Blueprint $table) {
            $table->dropIndex(['orcid']);
            $table->dropColumn(['email', 'orcid']);
            $table->renameColumn('author_order', 'position');
        });

        Schema::table('papers', function (Blueprint $table) {
            $table->dropColumn(['original_filename', 'mime_type', 'file_size']);
        });
    }

    private function addScoreConstraint(): void
    {
        match (DB::connection()->getDriverName()) {
            'sqlite' => $this->addSqliteScoreTriggers(),
            'mysql' => DB::statement(sprintf(
                'ALTER TABLE paper_scores ADD CONSTRAINT %s CHECK (score BETWEEN 0 AND 100)',
                self::SCORE_CONSTRAINT,
            )),
            'pgsql' => DB::statement(sprintf(
                'ALTER TABLE paper_scores ADD CONSTRAINT %s CHECK (score BETWEEN 0 AND 100)',
                self::SCORE_CONSTRAINT,
            )),
            default => throw new RuntimeException('Unsupported database driver for paper score constraint.'),
        };
    }

    private function dropScoreConstraint(): void
    {
        match (DB::connection()->getDriverName()) {
            'sqlite' => $this->dropSqliteScoreTriggers(),
            'mysql' => DB::statement(sprintf(
                str_contains(strtolower((string) DB::selectOne('SELECT VERSION() AS version')->version), 'mariadb')
                    ? 'ALTER TABLE paper_scores DROP CONSTRAINT %s'
                    : 'ALTER TABLE paper_scores DROP CHECK %s',
                self::SCORE_CONSTRAINT,
            )),
            'pgsql' => DB::statement(sprintf(
                'ALTER TABLE paper_scores DROP CONSTRAINT %s',
                self::SCORE_CONSTRAINT,
            )),
            default => throw new RuntimeException('Unsupported database driver for paper score constraint.'),
        };
    }

    private function addSqliteScoreTriggers(): void
    {
        DB::statement(
            'CREATE TRIGGER paper_scores_insert_range
             BEFORE INSERT ON paper_scores
             WHEN typeof(NEW.score) != "integer" OR NEW.score < 0 OR NEW.score > 100
             BEGIN
                 SELECT RAISE(ABORT, "paper score out of range");
             END',
        );

        DB::statement(
            'CREATE TRIGGER paper_scores_update_range
             BEFORE UPDATE OF score ON paper_scores
             WHEN typeof(NEW.score) != "integer" OR NEW.score < 0 OR NEW.score > 100
             BEGIN
                 SELECT RAISE(ABORT, "paper score out of range");
             END',
        );
    }

    private function dropSqliteScoreTriggers(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS paper_scores_insert_range');
        DB::statement('DROP TRIGGER IF EXISTS paper_scores_update_range');
    }
};
