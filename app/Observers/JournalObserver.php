<?php

namespace App\Observers;

use App\Models\Journal;
use App\Services\SearchIndexService;
use Illuminate\Support\Str;

class JournalObserver
{
    public function __construct(
        private readonly SearchIndexService $searchIndex,
    ) {}

    /**
     * Refresh the script-independent search columns on every write, so a
     * record stays findable in both Latin and Cyrillic the moment it's saved.
     */
    public function saving(Journal $journal): void
    {
        $this->searchIndex->fill($journal);
    }

    /**
     * Slug is generated automatically (from the name) and guaranteed to be unique.
     */
    public function creating(Journal $journal): void
    {
        if (empty($journal->slug)) {
            $journal->slug = $this->uniqueSlug($journal->name);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Journal::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
