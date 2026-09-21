<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Models\Paper;
use App\Models\User;
use App\Services\RecommendationHttpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReviewerRecommendationController extends Controller
{
    public function show(Request $request, Paper $paper, RecommendationHttpClient $client): JsonResponse
    {
        if (! $request->user()->hasRole(RoleName::Admin)) {
            abort(403);
        }

        // Gather paper details
        $title = $paper->title;
        $abstract = $paper->abstract ?? '';
        $keywords = $paper->analysis ? ($paper->analysis['keywords'] ?? []) : [];

        // Fetch eligible reviewers
        $reviewers = User::whereHas('roles', fn($q) => $q->where('name', RoleName::Reviewer->value))
            ->get(['id', 'name', 'expertise'])
            ->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'expertise' => $r->expertise,
            ])
            ->toArray();

        if (empty($reviewers)) {
            return response()->json(['recommendations' => []]);
        }

        try {
            $result = $client->recommend(
                (string) Str::uuid(),
                $title,
                $abstract,
                $keywords,
                $reviewers
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
