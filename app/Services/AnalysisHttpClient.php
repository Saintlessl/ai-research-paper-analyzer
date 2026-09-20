<?php

namespace App\Services;

use App\Models\Paper;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnalysisHttpClient
{
    /** @param resource $pdf */
    public function analyze(Paper $paper, string $requestId, $pdf): array
    {
        $response = Http::baseUrl((string) config('services.ai.url'))
            ->withToken((string) config('services.ai.token'))
            ->withHeaders(['X-Request-ID' => $requestId])
            ->timeout((int) config('services.ai.timeout', 120))
            ->attach('file', $pdf, basename((string) $paper->original_filename ?: 'paper.pdf'), ['Content-Type' => 'application/pdf'])
            ->post('/api/v1/analyze', [
                'request_id' => $requestId,
                'paper_id' => (string) $paper->id,
                'title' => $paper->title,
                'authors' => json_encode($paper->authors()->pluck('name')->all(), JSON_THROW_ON_ERROR),
            ]);
        if (! $response->successful() || $response->json('success') !== true || ! is_array($response->json('data'))) {
            throw new RuntimeException('AI analysis request failed.');
        }

        return $response->json('data');
    }
}
