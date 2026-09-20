<?php

namespace App\Jobs;

use App\Contracts\PaperProcessor;
use App\Enums\AiJobStatus;
use App\Models\AiJob;
use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessPaper implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public bool $failOnTimeout = true;

    public function __construct(public readonly int $aiJobId)
    {
        $this->afterCommit();
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(PaperProcessor $processor): void
    {
        $aiJob = AiJob::query()->with('paper')->findOrFail($this->aiJobId);
        $startedAt = now();
        $aiJob->update([
            'status' => AiJobStatus::Processing,
            'retry_count' => max(0, $this->attempts() - 1),
            'started_at' => $aiJob->started_at ?? $startedAt,
            'completed_at' => null,
            'error' => null,
            'error_code' => null,
            'error_message' => null,
        ]);
        $this->audit($aiJob, 'ai_job.processing', ['attempt' => max(1, $this->attempts())]);

        $processor->process($aiJob->paper, $aiJob->request_id);

        $completedAt = now();
        $aiJob->update([
            'status' => AiJobStatus::Completed,
            'duration_ms' => (int) $aiJob->started_at->diffInMilliseconds($completedAt),
            'completed_at' => $completedAt,
        ]);
        $this->audit($aiJob, 'ai_job.completed');
    }

    public function failed(?Throwable $exception): void
    {
        $aiJob = AiJob::query()->find($this->aiJobId);
        if ($aiJob === null) {
            return;
        }

        $completedAt = now();
        $startedAt = $aiJob->started_at ?? $completedAt;
        $errorCode = $exception === null ? 'UnknownError' : class_basename($exception);
        $aiJob->update([
            'status' => AiJobStatus::Failed,
            'retry_count' => $aiJob->max_retries,
            'error' => null,
            'error_code' => $errorCode,
            'error_message' => 'Paper processing failed.',
            'duration_ms' => (int) $startedAt->diffInMilliseconds($completedAt),
            'completed_at' => $completedAt,
        ]);
        $this->audit($aiJob, 'ai_job.failed', ['error_code' => $errorCode]);
    }

    /** @param array<string, int|string> $metadata */
    private function audit(AiJob $aiJob, string $action, array $metadata = []): void
    {
        AuditLog::query()->create([
            'actor_id' => $aiJob->user_id,
            'action' => $action,
            'target_type' => AiJob::class,
            'target_id' => $aiJob->id,
            'metadata' => ['request_id' => $aiJob->request_id, ...$metadata],
        ]);
    }
}