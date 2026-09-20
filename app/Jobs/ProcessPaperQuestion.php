<?php

namespace App\Jobs;

use App\Models\PaperQuestion;
use App\Services\QaHttpClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class ProcessPaperQuestion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public bool $failOnTimeout = true;

    public function __construct(public readonly int $questionId)
    {
        $this->afterCommit();
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function handle(QaHttpClient $qaClient): void
    {
        $question = PaperQuestion::query()->with('paper')->findOrFail($this->questionId);
        
        $requestId = $question->request_id ?: (string) Str::uuid();

        $question->update([
            'status' => 'PROCESSING',
            'request_id' => $requestId,
        ]);

        $response = $qaClient->ask($question->paper, $question->question, $requestId);

        $question->update([
            'status' => 'COMPLETED',
            'answer' => $response['answer'],
            'found' => $response['found'],
            'evidence' => $response['evidence'] ?? [],
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $question = PaperQuestion::query()->find($this->questionId);
        if ($question === null) {
            return;
        }

        $question->update([
            'status' => 'FAILED',
            'answer' => 'An error occurred while generating the answer.',
        ]);
    }
}
