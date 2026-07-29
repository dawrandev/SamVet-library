<?php

namespace App\Services\Lookups;

use App\Data\LookupData;
use App\Repositories\Eloquent\DepartmentCoverageRepository;

class DepartmentCoverageService extends BaseLookupService
{
    public function __construct(DepartmentCoverageRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes(LookupData $data): array
    {
        return [
            'name' => $data->name,
            'percentage' => $data->percentage ?? 0,
        ];
    }
}
