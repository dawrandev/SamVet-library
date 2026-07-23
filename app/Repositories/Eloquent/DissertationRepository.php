<?php

namespace App\Repositories\Eloquent;

use App\Models\Dissertation;
use App\Repositories\Contracts\DissertationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DissertationRepository implements DissertationRepositoryInterface
{
    /**
     * Eager loads to avoid N+1.
     *
     * @var array<int, string>
     */
    private const RELATIONS = ['resourceField'];

    public function filtered(array $filters = []): Builder
    {
        return Dissertation::query()
            // Search (title or author)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                });
            })
            ->when($filters['resource_field_id'] ?? null, function ($query, int $fieldId) {
                $query->where('resource_field_id', $fieldId);
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

    public function find(int $id): ?Dissertation
    {
        return Dissertation::with(self::RELATIONS)->find($id);
    }

    public function create(array $data): Dissertation
    {
        return Dissertation::create($data);
    }

    public function update(Dissertation $dissertation, array $data): Dissertation
    {
        $dissertation->update($data);

        return $dissertation;
    }

    public function delete(Dissertation $dissertation): void
    {
        $dissertation->delete();
    }
}
