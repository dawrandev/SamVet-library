<?php

namespace App\Observers;

use App\Models\Audiobook;
use Illuminate\Support\Str;

class AudiobookObserver
{
    /**
     * Slug is generated automatically (from the name) and guaranteed to be unique.
     */
    public function creating(Audiobook $audiobook): void
    {
        if (empty($audiobook->slug)) {
            $audiobook->slug = $this->uniqueSlug($audiobook->name);
        }
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
