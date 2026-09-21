<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeletePaperVectorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    
    public function __construct(public readonly int $paperId)
    {
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function handle(): void
    {
        $response = Http::baseUrl((string) config('services.ai.url'))
            ->withToken((string) config('services.ai.token'))
            ->timeout((int) config('services.ai.timeout', 60))
            ->delete("/api/v1/papers/{$this->paperId}");

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to delete vectors from AI Service: ' . $response->body());
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error("Failed to cascade delete vectors for paper_id {$this->paperId}: " . $exception?->getMessage());
    }
}
