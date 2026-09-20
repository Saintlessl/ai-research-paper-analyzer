<?php

namespace App\Http\Controllers;

use App\Models\Paper;
use App\Models\Review;
use App\Models\ReviewerAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewerAssignmentController extends Controller
{
    public function store(Request $request, Paper $paper): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'reviewer_id' => ['required', 'exists:users,id'],
        ]);

        $reviewer = User::findOrFail($validated['reviewer_id']);
        if (! $reviewer->hasRole('reviewer')) {
            return back()->withErrors(['reviewer_id' => 'User is not a reviewer.']);
        }

        ReviewerAssignment::firstOrCreate([
            'paper_id' => $paper->id,
            'reviewer_id' => $reviewer->id,
        ], [
            'status' => 'PENDING',
        ]);

        // Create an empty draft review
        Review::firstOrCreate([
            'paper_id' => $paper->id,
            'reviewer_id' => $reviewer->id,
        ], [
            'status' => 'DRAFT',
        ]);

        return back()->with('status', 'reviewer-assigned');
    }

    public function destroy(Request $request, Paper $paper, User $reviewer): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        ReviewerAssignment::where('paper_id', $paper->id)
            ->where('reviewer_id', $reviewer->id)
            ->delete();

        // Also delete the review if it's still a draft
        Review::where('paper_id', $paper->id)
            ->where('reviewer_id', $reviewer->id)
            ->where('status', 'DRAFT')
            ->delete();

        return back()->with('status', 'reviewer-removed');
    }
}
