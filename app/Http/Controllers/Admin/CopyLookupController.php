<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CopyStatus;
use App\Http\Controllers\Controller;
use App\Models\BookCopy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CopyLookupController extends Controller
{
    /**
     * Inventar raqami bo'yicha nusxa/kitob ma'lumotini qaytaradi (kitob berish modali autofill).
     */
    public function show(Request $request): JsonResponse
    {
        $inventory = $request->string('inventory')->trim()->toString();

        if ($inventory === '') {
            return response()->json(['found' => false]);
        }

        $copy = BookCopy::with('book.authors')
            ->where('inventory_number', $inventory)
            ->first();

        if ($copy === null) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'available' => $copy->status === CopyStatus::Available,
            'status' => $copy->status->label(),
            'copy' => [
                'inventory_number' => $copy->inventory_number,
                'format' => $copy->format->label(),
            ],
            'book' => [
                'title' => $copy->book?->title,
                'authors' => $copy->book?->authors->pluck('name')->implode(', '),
                'udc' => $copy->book?->udc,
                'year' => $copy->book?->publication_year,
            ],
        ]);
    }
}
