<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPaperQuestion;
use App\Models\Paper;
use App\Models\PaperQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class PaperQuestionController extends Controller
{
    public function store(Request $request, Paper $paper): RedirectResponse
    {
        Gate::authorize('askQuestion', $paper);

        if ($paper->status->value !== 'ANALYZED') {
            return back()->withErrors(['qa' => 'PAPER_NOT_READY']);
        }

        $validated = $request->validate([
            'question' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $question = $paper->questions()->create([
            'user_id' => $request->user()->id,
            'question' => $validated['question'],
            'status' => 'PENDING',
            'request_id' => (string) Str::uuid(),
        ]);

        ProcessPaperQuestion::dispatch($question->id);

        return back()->with('status', 'question-asked');
    }
}
