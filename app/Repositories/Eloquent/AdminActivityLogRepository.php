<?php

namespace App\Repositories\Eloquent;

use App\Models\AdminActivityLog;
use App\Repositories\Contracts\AdminActivityLogRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminActivityLogRepository implements AdminActivityLogRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AdminActivityLog
    {
        return AdminActivityLog::create($data);
    }

    public function paginate(array $filters, int $perPage = 30): LengthAwarePaginator
    {
        return AdminActivityLog::query()
            ->with('admin')
            ->when($filters['subject_type'] ?? null, fn ($query, string $type) => $query->where('subject_type', $type))
            ->when($filters['action'] ?? null, fn ($query, string $action) => $query->where('action', $action))
            ->when($filters['admin_id'] ?? null, fn ($query, int $adminId) => $query->where('admin_id', $adminId))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
