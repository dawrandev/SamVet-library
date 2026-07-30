<?php

namespace App\Repositories\Contracts;

use App\Models\AdminActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;

interface AdminActivityLogRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AdminActivityLog;

    /**
     * @param  array<string, mixed>  $filters  optional: subject_type, action, admin_id
     */
    public function paginate(array $filters, int $perPage = 30): LengthAwarePaginator;
}
