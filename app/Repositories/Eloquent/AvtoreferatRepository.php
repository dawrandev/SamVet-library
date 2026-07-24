<?php

namespace App\Repositories\Eloquent;

use App\Models\Avtoreferat;
use App\Repositories\Contracts\AvtoreferatRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AvtoreferatRepository implements AvtoreferatRepositoryInterface
{
    /**
     * Eager loads to avoid N+1.
     *
     * @var array<int, string>
     */
    private const RELATIONS = [
        'publicationPlace',
    ];

    public function filtered(array $filters = []): Builder
    {
        return Avtoreferat::query()
            // Search (title or author)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                });
            });
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->with(self::RELATIONS)
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
