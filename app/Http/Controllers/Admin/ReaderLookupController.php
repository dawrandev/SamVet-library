<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ReaderRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReaderLookupController extends Controller
{
    public function __construct(
        private readonly ReaderRepositoryInterface $readers,
    ) {}

    /**
     * Returns reader information by ID number (autofill for the "lend from
     * the book side" modal) — mirrors CopyLookupController::show().
     */
    public function show(Request $request): JsonResponse
    {
        $idNumber = $request->string('id_number')->trim()->toString();

        if ($idNumber === '') {
            return response()->json(['found' => false]);
        }

        $reader = $this->readers->findByIdNumber($idNumber);

        if ($reader === null) {
            return response()->json(['found' => false]);
        }

        $isStudent = $reader->type->isStudent();

        return response()->json([
            'found' => true,
            'reader_id' => $reader->id,
            'full_name' => $reader->full_name,
            'id_number' => $reader->id_number,
            'can_borrow' => $reader->canBorrow(),
            'status' => $reader->status->label(),
            'affiliation' => [
                'place_label' => $isStudent ? __('O‘qish joyi') : __('Ish joyi'),
                'place' => $reader->affiliationPlace?->name,
                'unit_label' => $isStudent ? __('Mutaxassisligi') : __('Bo‘limi'),
                'unit' => $reader->affiliationUnit?->name,
                'group_label' => $isStudent ? __('Guruhi') : __('Lavozimi'),
                'group' => $reader->affiliationGroup?->name,
            ],
        ]);
    }
}
