<?php

namespace App\Repositories\Eloquent;

use App\Models\Audiobook;
use App\Repositories\Contracts\AudiobookRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AudiobookRepository implements AudiobookRepositoryInterface
{
    public function filtered(array $filters = []): Builder
    {
        return Audiobook::query()
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                });
            });
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->withCount('tracks')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Audiobook
    {
        return Audiobook::with('tracks')->find($id);
    }

    public function findBySlug(string $slug): ?Audiobook
    {
        return Audiobook::with('tracks')->where('slug', $slug)->first();
    }

    public function create(array $data): Audiobook
    {
        return Audiobook::create($data);
    }

    public function update(Audiobook $audiobook, array $data): Audiobook
    {
        $audiobook->update($data);

        return $audiobook;
    }

    public function delete(Audiobook $audiobook): void
    {
        $audiobook->delete();
    }

    public function incrementViews(Audiobook $audiobook): void
    {
        $audiobook->increment('views_count');
    }

    public function similar(Audiobook $audiobook, int $limit): Collection
    {
        return Audiobook::query()
            ->withCount('tracks')
            ->where('id', '!=', $audiobook->id)
            ->latest('id')
            ->take($limit)
            ->get();
    }
}
