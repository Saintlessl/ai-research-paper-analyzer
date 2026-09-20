<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPaperComparison;
use App\Models\Paper;
use App\Models\PaperComparison;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class PaperComparisonController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $request->user();
        // Load analyzed papers the user has access to
        $papers = Paper::query()
            ->where('status', 'ANALYZED')
            ->when($user->hasRole('researcher'), fn ($q) => $q->where('uploaded_by', $user->id))
            ->get(['id', 'title', 'publication_year', 'journal']);

        return Inertia::render('Papers/Compare', [
            'papers' => $papers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'paper_a_id' => ['required', 'exists:papers,id', 'different:paper_b_id'],
            'paper_b_id' => ['required', 'exists:papers,id'],
        ]);

        // User must have view access to both papers
        $paperA = Paper::findOrFail($validated['paper_a_id']);
        $paperB = Paper::findOrFail($validated['paper_b_id']);
        $this->authorize('view', $paperA);
        $this->authorize('view', $paperB);

        $comparison = PaperComparison::create([
            'user_id' => $request->user()->id,
            'paper_a_id' => $paperA->id,
            'paper_b_id' => $paperB->id,
            'status' => 'PENDING',
            'request_id' => (string) Str::uuid(),
        ]);

        ProcessPaperComparison::dispatch($comparison->id);

        return redirect()->route('papers.compare.show', $comparison);
    }

    public function show(Request $request, PaperComparison $comparison): Response
    {
        if ($comparison->user_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $comparison->load(['paperA', 'paperB']);

        return Inertia::render('Papers/Compare', [
            'comparison' => $comparison,
        ]);
    }
}
