<?php

namespace App\Observers;

use App\Models\Journal;
use Illuminate\Support\Str;

class JournalObserver
{
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
