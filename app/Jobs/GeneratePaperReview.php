<?php

namespace App\Jobs;

use App\Models\Paper;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewHttpClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class GeneratePaperReview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public bool $failOnTimeout = true;

    public function __construct(public readonly int $paperId, public readonly string $requestId)
    {
        $this->afterCommit();
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(ReviewHttpClient $reviewClient): void
    {
        $paper = Paper::query()->findOrFail($this->paperId);
        
        $aiUser = User::firstOrCreate(
            ['email' => 'ai@system.local'],
            [
                'name' => 'AI Reviewer',
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]
        );

        $review = Review::updateOrCreate(
            ['paper_id' => $paper->id, 'reviewer_id' => $aiUser->id],
            ['status' => 'PROCESSING']
        );

        $response = $reviewClient->review($paper, $this->requestId);

        $review->update([
            'status' => 'COMPLETED',
            'summary' => $response['summary'] ?? '',
            'strengths' => $response['strengths'] ?? [],
            'major_concerns' => $response['major_concerns'] ?? [],
            'minor_concerns' => $response['minor_concerns'] ?? [],
            'methodology_review' => $response['methodology_review'] ?? '',
            'novelty_review' => $response['novelty_review'] ?? '',
            'results_review' => $response['results_review'] ?? '',
            'reproducibility_review' => $response['reproducibility_review'] ?? '',
            'recommendation' => $response['recommendation'] ?? 'NONE',
            'recommendation_reason' => $response['recommendation_reason'] ?? '',
            'evidence' => $response['evidence'] ?? [],
            'submitted_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $aiUser = User::where('email', 'ai@system.local')->first();
        if ($aiUser) {
            Review::where('paper_id', $this->paperId)
                ->where('reviewer_id', $aiUser->id)
                ->update([
                    'status' => 'FAILED',
                    'summary' => 'AI Review generation failed.',
                ]);
        }
    }
}
