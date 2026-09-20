<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class RecommendationHttpClient
{
    public function recommend(string $requestId, string $title, string $abstract, array $keywords, array $reviewers): array
    {
        $response = Http::baseUrl((string) config('services.ai.url'))
            ->withToken((string) config('services.ai.token'))
            ->withHeaders(['X-Request-ID' => $requestId])
            ->timeout(60)
            ->post('/api/v1/recommend-reviewers', [
                'request_id' => $requestId,
                'paper_title' => $title,
                'paper_abstract' => $abstract,
                'paper_keywords' => $keywords,
                'reviewers' => $reviewers,
            ]);

        if (! $response->successful() || $response->json('success') !== true || ! is_array($response->json('data'))) {
            throw new RuntimeException('AI Recommendation request failed: ' . $response->body());
        }

        return $response->json('data');
    }
}
