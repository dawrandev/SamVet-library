<?php

namespace App\Observers;

use App\Models\Audiobook;
use App\Services\SearchIndexService;
use Illuminate\Support\Str;

class AudiobookObserver
{
    public function __construct(
        private readonly SearchIndexService $searchIndex,
    ) {}

    /**
     * Slug is generated automatically (from the name) and guaranteed to be unique.
     */
    public function creating(Audiobook $audiobook): void
    {
        if (empty($audiobook->slug)) {
            $audiobook->slug = $this->uniqueSlug($audiobook->name);
        }
    }

    /**
     * Refresh the script-independent search columns on every write, so a
     * record stays findable in both Latin and Cyrillic the moment it's saved.
     */
    public function saving(Audiobook $audiobook): void
    {
        $this->searchIndex->fill($audiobook);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Audiobook::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
