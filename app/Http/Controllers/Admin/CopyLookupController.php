<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CopyStatus;
use App\Http\Controllers\Controller;
use App\Models\BookCopy;
use App\Models\JournalCopy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CopyLookupController extends Controller
{
    /**
     * Returns copy/material information by inventory number (autofill for the
     * lending modal) — searches book copies first, then journal/newspaper copies.
     */
    public function show(Request $request): JsonResponse
    {
        $inventory = $request->string('inventory')->trim()->toString();

        if ($inventory === '') {
            return response()->json(['found' => false]);
        }

        $bookCopy = BookCopy::with('book')->where('inventory_number', $inventory)->first();

        if ($bookCopy !== null) {
            return response()->json([
                'found' => true,
                'type' => 'book',
                'available' => $bookCopy->status === CopyStatus::Available,
                'status' => $bookCopy->status->label(),
                'copy' => [
                    'inventory_number' => $bookCopy->inventory_number,
                    'format' => $bookCopy->format->label(),
                ],
                'book' => [
                    'title' => $bookCopy->book?->title,
                    'authors' => $bookCopy->book?->authors,
                    'udc' => $bookCopy->book?->udc,
                    'year' => $bookCopy->book?->publication_year,
                ],
            ]);
        }

        $journalCopy = JournalCopy::with('issue.journal')->where('inventory_number', $inventory)->first();

        if ($journalCopy !== null) {
            return response()->json([
                'found' => true,
                'type' => 'journal_copy',
                'available' => $journalCopy->status === CopyStatus::Available,
                'status' => $journalCopy->status->label(),
                'copy' => [
                    'inventory_number' => $journalCopy->inventory_number,
                ],
                'journal' => [
                    'name' => $journalCopy->issue?->journal?->name,
                    'kind' => $journalCopy->issue?->journal?->kind?->label(),
                    'year' => $journalCopy->issue?->year,
                    'issue_number' => $journalCopy->issue?->issue_number,
                ],
            ]);
        }

        return response()->json(['found' => false]);
    }
}
