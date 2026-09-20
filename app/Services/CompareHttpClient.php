<?php

namespace App\Services;

use App\Models\Paper;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CompareHttpClient
{
    public function compare(Paper $paperA, Paper $paperB, string $requestId): array
    {
        $textA = $paperA->sections()->orderBy('id')->pluck('content')->implode("\n\n");
        $textB = $paperB->sections()->orderBy('id')->pluck('content')->implode("\n\n");

        if (empty(trim($textA)) || empty(trim($textB))) {
            throw new RuntimeException('Cannot perform comparison: Paper text extraction is empty.');
        }

        $response = Http::baseUrl((string) config('services.ai.url'))
            ->withToken((string) config('services.ai.token'))
            ->withHeaders(['X-Request-ID' => $requestId])
            ->timeout((int) config('services.ai.timeout', 120))
            ->post('/api/v1/compare', [
                'request_id' => $requestId,
                'paper_a' => [
                    'paper_id' => $paperA->id,
                    'text' => $textA,
                ],
                'paper_b' => [
                    'paper_id' => $paperB->id,
                    'text' => $textB,
                ],
            ]);

        if (! $response->successful() || $response->json('success') !== true || ! is_array($response->json('data'))) {
            throw new RuntimeException('AI Comparison request failed: ' . $response->body());
        }

        return $response->json('data');
    }
}
