<?php

namespace App\Repositories\Eloquent;

use App\Enums\ReaderStatus;
use App\Models\Reader;
use App\Repositories\Contracts\ReaderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReaderRepository implements ReaderRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Reader::query()
            // Qidiruv (F.I.SH, ID raqami, PINFL)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('id_number', 'like', "%{$search}%")
                        ->orWhere('pinfl', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] ?? null, function ($query, string $type) {
                $query->where('type', $type);
            })
            ->when($filters['status'] ?? null, function ($query, string $status) {
                $query->where('status', $status);
            })
            // Status filtri berilmasa — "Ketgan" (left) a'zolar asosiy ro'yxatda ko'rinmaydi.
            // (Yozuv saqlanadi; ko'rish uchun status=left filtrini tanlash kerak.)
            ->when(empty($filters['status']), function ($query) {
                $query->where('status', '!=', ReaderStatus::Left->value);
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Reader
    {
        return Reader::find($id);
    }

    public function create(array $data): Reader
    {
        return Reader::create($data);
    }

    public function update(Reader $reader, array $data): Reader
    {
        $reader->update($data);

        return $reader;
    }

    public function delete(Reader $reader): void
    {
        $reader->delete();
    }
}
