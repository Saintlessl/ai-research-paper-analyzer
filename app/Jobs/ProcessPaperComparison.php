<?php

namespace App\Jobs;

use App\Models\PaperComparison;
use App\Services\CompareHttpClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class ProcessPaperComparison implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public bool $failOnTimeout = true;

    public function __construct(public readonly int $comparisonId)
    {
        $this->afterCommit();
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function handle(CompareHttpClient $compareClient): void
    {
        $comparison = PaperComparison::query()->with(['paperA', 'paperB'])->findOrFail($this->comparisonId);
        
        $requestId = $comparison->request_id ?: (string) Str::uuid();

        $comparison->update([
            'status' => 'PROCESSING',
            'request_id' => $requestId,
        ]);

        $response = $compareClient->compare($comparison->paperA, $comparison->paperB, $requestId);

        $comparison->update([
            'status' => 'COMPLETED',
            'dimensions' => $response['dimensions'] ?? [],
            'conclusion' => $response['conclusion'] ?? '',
            'reasoning' => $response['reasoning'] ?? '',
            'evidence' => $response['evidence'] ?? [],
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $comparison = PaperComparison::query()->find($this->comparisonId);
        if ($comparison === null) {
            return;
        }

        $comparison->update([
            'status' => 'FAILED',
            'conclusion' => 'An error occurred while generating the comparison.',
        ]);
    }
}
