<?php

namespace App\Repositories\Eloquent;

use App\Models\News;
use App\Repositories\Contracts\NewsRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NewsRepository implements NewsRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return News::query()
            ->with('category')
            // Search (title — JSON LIKE across all three languages)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title->uz', 'like', "%{$search}%")
                        ->orWhere('title->ru', 'like', "%{$search}%")
                        ->orWhere('title->kk', 'like', "%{$search}%");
                });
            })
            ->when($filters['news_category_id'] ?? null, function ($query, int $categoryId) {
                $query->where('news_category_id', $categoryId);
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?News
    {
        return News::with(['category', 'images'])->find($id);
    }

    public function create(array $data): News
    {
        return News::create($data);
    }

    public function update(News $news, array $data): News
    {
        $news->update($data);

        return $news;
    }

    public function delete(News $news): void
    {
        $news->delete();
    }
}
