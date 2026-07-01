<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLookupRequest;
use App\Services\LookupService;
use Illuminate\Http\JsonResponse;

class LookupController extends Controller
{
    public function __construct(
        private readonly LookupService $lookups,
    ) {}

    /**
     * Formadan turib yangi lookup yaratish (AJAX).
     */
    public function store(StoreLookupRequest $request): JsonResponse
    {
        $data = $this->lookups->create(
            $request->string('type')->toString(),
            $request->validated(),
        );

        return response()->json($data, 201);
    }
}
