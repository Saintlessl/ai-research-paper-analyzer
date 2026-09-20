<?php

namespace Tests\Feature;

use App\Contracts\PaperProcessor;
use App\Enums\AiJobStatus;
use App\Enums\PaperStatus;
use App\Enums\RoleName;
use App\Jobs\ProcessPaper;
use App\Models\AiJob;
use App\Models\AuditLog;
use App\Models\Paper;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class AiJobTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('paper-files');
        config()->set('papers.storage_disk', 'paper-files');
    }

    public function test_upload_atomically_creates_pending_ai_job_and_dispatches_it_after_commit(): void
    {
        Queue::fake();
        $researcher = $this->researcher();

        $this->actingAs($researcher)->post('/papers', [
            'title' => 'Queue tracked paper',
            'file' => $this->pdf(),
        ])->assertRedirect();

        $paper = Paper::query()->sole();
        $aiJob = AiJob::query()->sole();

        $this->assertSame($paper->id, $aiJob->paper_id);
        $this->assertSame($researcher->id, $aiJob->user_id);
        $this->assertSame('paper.analysis', $aiJob->type);
        $this->assertSame(AiJobStatus::Pending, $aiJob->status);
        $this->assertSame(0, $aiJob->retry_count);
        $this->assertSame(3, $aiJob->max_retries);
        $this->assertNotEmpty($aiJob->request_id);

        Queue::assertPushed(ProcessPaper::class, function (ProcessPaper $job) use ($aiJob): bool {
            return $job->aiJobId === $aiJob->id && $job->afterCommit;
        });

        $audit = AuditLog::query()->where('action', 'ai_job.created')->sole();
        $this->assertSame($aiJob->id, $audit->target_id);
        $this->assertSame($aiJob->request_id, $audit->metadata['request_id']);
        $this->assertArrayNotHasKey('file_path', $audit->metadata);
        $this->assertArrayNotHasKey('checksum_sha256', $audit->metadata);
    }

    public function test_queued_job_tracks_processing_and_completion_with_explicit_worker_limits(): void
    {
        $paper = $this->paper();
        $aiJob = $paper->aiJobs()->create([
            'request_id' => (string) str()->uuid(),
            'user_id' => $paper->uploaded_by,
            'type' => 'paper.analysis',
            'status' => AiJobStatus::Pending,
            'max_retries' => 3,
        ]);
        $processor = new class implements PaperProcessor
        {
            public function process(Paper $paper, string $requestId): void {}
        };
        $job = new ProcessPaper($aiJob->id);

        $job->handle($processor);

        $aiJob->refresh();
        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 30, 60], $job->backoff());
        $this->assertSame(120, $job->timeout);
        $this->assertSame(AiJobStatus::Completed, $aiJob->status);
        $this->assertNotNull($aiJob->started_at);
        $this->assertNotNull($aiJob->completed_at);
        $this->assertNotNull($aiJob->duration_ms);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ai_job.completed',
            'target_type' => AiJob::class,
            'target_id' => $aiJob->id,
        ]);
    }

    public function test_terminal_failure_marks_ai_job_failed_without_deleting_paper(): void
    {
        $paper = $this->paper();
        $aiJob = $paper->aiJobs()->create([
            'request_id' => (string) str()->uuid(),
            'user_id' => $paper->uploaded_by,
            'type' => 'paper.analysis',
            'status' => AiJobStatus::Processing,
            'max_retries' => 3,
            'started_at' => now(),
        ]);
        $job = new ProcessPaper($aiJob->id);

        $job->failed(new RuntimeException('provider secret=do-not-log'));

        $aiJob->refresh();
        $this->assertSame(AiJobStatus::Failed, $aiJob->status);
        $this->assertSame('RuntimeException', $aiJob->error_code);
        $this->assertSame('Paper processing failed.', $aiJob->error_message);
        $this->assertSame(3, $aiJob->retry_count);
        $this->assertNotNull($aiJob->completed_at);
        $this->assertDatabaseHas('papers', ['id' => $paper->id]);
        $this->assertSame(PaperStatus::Uploaded, $paper->fresh()->status);

        $audit = AuditLog::query()->where('action', 'ai_job.failed')->sole();
        $this->assertSame($aiJob->request_id, $audit->metadata['request_id']);
        $this->assertStringNotContainsString('do-not-log', json_encode($audit->metadata, JSON_THROW_ON_ERROR));
    }

    private function researcher(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', RoleName::Researcher->value)->firstOrFail());

        return $user;
    }

    private function paper(): Paper
    {
        $user = $this->researcher();

        return Paper::query()->create([
            'title' => 'Tracked paper',
            'file_path' => 'papers/test.pdf',
            'storage_disk' => 'paper-files',
            'original_filename' => 'paper.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'checksum_sha256' => str_repeat('a', 64),
            'status' => PaperStatus::Uploaded,
            'uploaded_by' => $user->id,
        ]);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'paper.pdf',
            "%PDF-1.7\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n",
        );
    }
}
