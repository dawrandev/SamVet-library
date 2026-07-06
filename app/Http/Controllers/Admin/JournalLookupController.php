<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\JournalSearchResource;
use App\Models\Journal;
use App\Models\JournalIssue;
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

        if ($term !== '') {
            $query->where('name', 'like', "%{$term}%");
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
