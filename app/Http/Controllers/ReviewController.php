<?php

namespace App\Http\Controllers;

use App\Jobs\GeneratePaperReview;
use App\Models\Paper;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function store(Request $request, Paper $paper): RedirectResponse
    {
        // This endpoint triggers an AI review
        Gate::authorize('update', $paper); // Owner or admin can request AI review

        GeneratePaperReview::dispatch($paper->id, (string) Str::uuid());

        return back()->with('status', 'ai-review-requested');
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        // Reviewers update their assigned review
        if ($review->reviewer_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'summary' => ['required', 'string'],
            'strengths' => ['nullable', 'array'],
            'major_concerns' => ['nullable', 'array'],
            'minor_concerns' => ['nullable', 'array'],
            'recommendation' => ['required', 'string'],
        ]);

        $review->update(array_merge($validated, [
            'status' => 'SUBMITTED',
            'submitted_at' => now(),
        ]));
        
        // Also update assignment status
        $review->paper->assignments()->where('reviewer_id', $review->reviewer_id)->update([
            'status' => 'COMPLETED',
            'completed_at' => now(),
        ]);

        return back()->with('status', 'review-submitted');
    }
}
