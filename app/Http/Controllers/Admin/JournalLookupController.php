<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\JournalSearchResource;
use App\Models\Journal;
use App\Models\JournalIssue;
use App\Support\SearchNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class JournalLookupController extends Controller
{
    /**
     * Live-search journals by name (autocomplete in the article form).
     */
    public function show(Request $request): AnonymousResourceCollection
    {
        $term = $request->string('q')->trim()->toString();

        $query = Journal::query()->with('type')->withCount('issues');

        // Normalized on both sides, so typing the journal's name in either
        // script autocompletes it (see SearchNormalizer). A term that
        // normalizes away to nothing (punctuation only) is treated as no term
        // at all, which for an autocomplete means "show the first few".
        $normalized = SearchNormalizer::normalize($term);

        if ($normalized !== '') {
            $query->where('search_text', 'like', '%'.$normalized.'%');
        }

        // Scopes results to journals or newspapers only — used when the form
        // was reached via a kind-specific "Yangi ... maqolasi" button.
        if ($request->filled('kind')) {
            $query->where('kind', $request->string('kind')->toString());
        }

        $journals = $query->orderBy('name')->limit(10)->get();

        return JournalSearchResource::collection($journals);
    }

    /**
     * Issues of a given journal (dependent select in the article form).
     */
    public function issues(Journal $journal): JsonResponse
    {
        $issues = $journal->issues()
            ->orderByDesc('year')
            ->get(['id', 'issue_number', 'year'])
            ->map(fn (JournalIssue $issue): array => [
                'id' => $issue->id,
                'issue_number' => $issue->issue_number,
                'year' => $issue->year,
            ]);

        return response()->json(['issues' => $issues]);
    }
}
