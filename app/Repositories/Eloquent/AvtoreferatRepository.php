<?php

namespace App\Repositories\Eloquent;

use App\Models\Avtoreferat;
use App\Repositories\Concerns\NormalizedSearch;
use App\Repositories\Contracts\AvtoreferatRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AvtoreferatRepository implements AvtoreferatRepositoryInterface
{
    use NormalizedSearch;

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
            // Search — script-independent (see NormalizedSearch).
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $search) => $this->applyNormalizedSearch($query, $search)
            );
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

    public function findBySlug(string $slug): ?Avtoreferat
    {
        return Avtoreferat::query()
            ->with(['scienceField', 'publicationPlace', 'contributors.contributorRole', 'languages'])
            ->where('slug', $slug)
            ->first();
    }

    public function incrementViews(Avtoreferat $avtoreferat): void
    {
        $avtoreferat->increment('views_count');
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
