<?php

namespace App\Observers;

use App\Models\Avtoreferat;
use Illuminate\Support\Str;

class AvtoreferatObserver
{
    /**
     * Slug is generated automatically (from the title) and guaranteed to be unique.
     */
    public function creating(Avtoreferat $avtoreferat): void
    {
        if (empty($avtoreferat->slug)) {
            $avtoreferat->slug = $this->uniqueSlug($avtoreferat->title);
        }
    }

    /**
     * Keep the slug in sync when the title changes.
     */
    public function updating(Avtoreferat $avtoreferat): void
    {
        if ($avtoreferat->isDirty('title')) {
            $avtoreferat->slug = $this->uniqueSlug($avtoreferat->title, $avtoreferat->id);
        }
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'avtoreferat';
        $slug = $base;
        $i = 1;

        while (Avtoreferat::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
