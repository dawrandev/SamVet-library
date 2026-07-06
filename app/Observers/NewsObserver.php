<?php

namespace App\Observers;

use App\Models\News;
use Illuminate\Support\Str;

class NewsObserver
{
    /**
     * Slug is generated automatically (from the title, first available language: uz→ru→kk),
     * and guaranteed to be unique.
     */
    public function creating(News $news): void
    {
        if (empty($news->slug)) {
            $news->slug = $this->uniqueSlug($this->sourceTitle($news));
        }
    }

    /**
     * Source title for the slug: the first filled language (uz→ru→kk).
     */
    private function sourceTitle(News $news): string
    {
        foreach (['uz', 'ru', 'kk'] as $locale) {
            $value = trim((string) $news->getTranslation('title', $locale, false));

            if ($value !== '') {
                return $value;
            }
        }

        return 'news';
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'news';
        $slug = $base;
        $i = 1;

        while (News::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
