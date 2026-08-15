<?php

namespace App\Observers;

use App\Models\Article;
use App\Services\SearchIndexService;
use Illuminate\Support\Str;

class ArticleObserver
{
    public function __construct(
        private readonly SearchIndexService $searchIndex,
    ) {}

    /**
     * Refresh the script-independent search columns on every write, so a
     * record stays findable in both Latin and Cyrillic the moment it's saved.
     */
    public function saving(Article $article): void
    {
        $this->searchIndex->fill($article);
    }

    /**
     * Slug is generated automatically (from the title) and guaranteed to be unique.
     */
    public function creating(Article $article): void
    {
        if (empty($article->slug)) {
            $article->slug = $this->uniqueSlug($article->title, $article->id);
        }
    }

    /**
     * Keep the slug in sync when the title changes.
     */
    public function updating(Article $article): void
    {
        if ($article->isDirty('title')) {
            $article->slug = $this->uniqueSlug($article->title, $article->id);
        }
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        $i = 1;

        while (Article::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
