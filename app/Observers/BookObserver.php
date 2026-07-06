<?php

namespace App\Observers;

use App\Models\Book;
use Illuminate\Support\Str;

class BookObserver
{
    /**
     * Slug is generated automatically (from the title) and guaranteed to be unique.
     */
    public function creating(Book $book): void
    {
        if (empty($book->slug)) {
            $book->slug = $this->uniqueSlug($book->title);
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (Book::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
