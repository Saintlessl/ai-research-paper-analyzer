<?php

namespace App\Services;

use App\Models\Paper;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ReviewHttpClient
{
    public function review(Paper $paper, string $requestId): array
    {
        $text = $paper->sections()->orderBy('id')->pluck('content')->implode("\n\n");

        if (empty(trim($text))) {
            throw new RuntimeException('Cannot perform review: Paper text extraction is empty.');
        }

        $response = Http::baseUrl((string) config('services.ai.url'))
            ->withToken((string) config('services.ai.token'))
            ->withHeaders(['X-Request-ID' => $requestId])
            ->timeout((int) config('services.ai.timeout', 120))
            ->post('/api/v1/review', [
                'request_id' => $requestId,
                'paper_id' => $paper->id,
                'text' => $text,
            ]);

        if (! $response->successful() || $response->json('success') !== true || ! is_array($response->json('data'))) {
            throw new RuntimeException('AI Review request failed: ' . $response->body());
        }

        return $response->json('data');
    }
}
