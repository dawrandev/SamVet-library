<?php

namespace App\Services\Lookups;

use App\Data\LookupData;
use App\Repositories\Eloquent\DistrictRepository;

class DistrictService extends BaseLookupService
{
    public function __construct(DistrictRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * A district optionally belongs to a region (LookupData's generic parent_id).
     *
     * @return array<string, mixed>
     */
    protected function attributes(LookupData $data): array
    {
        return [
            'name' => $data->name,
            'region_id' => $data->parent_id,
        ];
    }
}
