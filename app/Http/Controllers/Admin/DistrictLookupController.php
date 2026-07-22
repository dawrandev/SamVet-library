<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Region;
use Illuminate\Http\JsonResponse;

class DistrictLookupController extends Controller
{
    /**
     * Districts of a given region (dependent select in the reader form).
     */
    public function byRegion(Region $region): JsonResponse
    {
        $districts = $region->districts()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (District $district): array => [
                'id' => $district->id,
                'name' => $district->name,
            ]);

        return response()->json(['districts' => $districts]);
    }
}
