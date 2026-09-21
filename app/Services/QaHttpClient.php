<?php

namespace App\Services;

use App\Models\Paper;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class QaHttpClient
{
    public function ask(Paper $paper, string $question, string $requestId): array
    {
        $response = Http::baseUrl((string) config('services.ai.url'))
            ->withToken((string) config('services.ai.token'))
            ->withHeaders(['X-Request-ID' => $requestId])
            ->timeout((int) config('services.ai.timeout', 120))
            ->post('/api/v1/qa', [
                'request_id' => $requestId,
                'paper_id' => $paper->id,
                'question' => $question,
            ]);

        if (! $response->successful() || $response->json('success') !== true || ! is_array($response->json('data'))) {
            throw new RuntimeException('AI Q&A request failed: ' . $response->body());
        }

        return $response->json('data');
    }
}
