<?php

namespace App\Observers;

use App\Models\Dissertation;
use Illuminate\Support\Str;

class DissertationObserver
{
    /**
     * Slug is generated automatically (from the title) and guaranteed to be unique.
     */
    public function creating(Dissertation $dissertation): void
    {
        if (empty($dissertation->slug)) {
            $dissertation->slug = $this->uniqueSlug($dissertation->title);
        }
    }

    /**
     * Keep the slug in sync when the title changes.
     */
    public function updating(Dissertation $dissertation): void
    {
        if ($dissertation->isDirty('title')) {
            $dissertation->slug = $this->uniqueSlug($dissertation->title, $dissertation->id);
        }
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'dissertation';
        $slug = $base;
        $i = 1;

        while (Dissertation::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
