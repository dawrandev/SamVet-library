<?php

namespace App\Repositories\Eloquent;

use App\Models\News;
use App\Models\NewsCategory;
use App\Repositories\Contracts\NewsRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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

    public function publishedPaginated(?int $categoryId, int $perPage): LengthAwarePaginator
    {
        return $this->published(News::query()->with('category'))
            ->when($categoryId, fn (Builder $q, int $id) => $q->where('news_category_id', $id))
            ->latest('published_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function publishedCategories(): Collection
    {
        $locale = app()->getLocale();

        return NewsCategory::query()
            ->whereHas('news', fn (Builder $q) => $this->published($q))
            ->orderBy('id')
            ->get()
            ->map(fn (NewsCategory $c): array => [
                'id' => $c->id,
                'label' => $c->getTranslation('name', $locale, false) ?: $c->getTranslation('name', 'uz', false),
            ]);
    }

    public function findPublishedBySlug(string $slug): ?News
    {
        return $this->published(News::query()->with(['category', 'images']))
            ->where('slug', $slug)
            ->first();
    }

    public function latestPublishedExcept(int $exceptId, int $limit): Collection
    {
        return $this->published(News::query()->with('category'))
            ->whereKeyNot($exceptId)
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function incrementViews(News $news): void
    {
        $news->increment('views_count');
    }

    /** Constrain a query to items that are actually published (date set and due). */
    private function published(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
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
