<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewCommentController extends Controller
{
    public function store(Request $request, Review $review): RedirectResponse
    {
        $request->validate([
            'body' => 'required|string|max:2000',
            'page' => 'nullable|integer|min:1',
        ]);

        // Only the assigned reviewer or admin can comment
        $user = $request->user();
        if ($review->reviewer_id !== $user->id && ! $user->hasRole('admin')) {
            abort(403);
        }

        $review->comments()->create([
            'user_id' => $user->id,
            'body' => $request->body,
            'page' => $request->page,
        ]);

        return back();
    }
}
