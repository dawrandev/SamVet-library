<?php

namespace App\Repositories\Eloquent;

use App\Models\Avtoreferat;
use App\Repositories\Contracts\AvtoreferatRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AvtoreferatRepository implements AvtoreferatRepositoryInterface
{
    /**
     * Eager loads to avoid N+1.
     *
     * @var array<int, string>
     */
    private const RELATIONS = [
        'resourceField',
        'publicationPlace',
    ];

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Avtoreferat::query()
            ->with(self::RELATIONS)
            // Search (title or author)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                });
            })
            ->when($filters['resource_field_id'] ?? null, function ($query, int $fieldId) {
                $query->where('resource_field_id', $fieldId);
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Avtoreferat
    {
        return Avtoreferat::with(self::RELATIONS)->find($id);
    }

    public function create(array $data): Avtoreferat
    {
        return Avtoreferat::create($data);
    }

    public function update(Avtoreferat $avtoreferat, array $data): Avtoreferat
    {
        $avtoreferat->update($data);

        return $avtoreferat;
    }

    public function delete(Avtoreferat $avtoreferat): void
    {
        $avtoreferat->delete();
    }
}
