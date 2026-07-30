<?php

namespace App\Observers;

use App\Enums\AdminActivityAction;
use App\Models\Book;
use App\Services\AdminActivityLogService;
use Illuminate\Support\Str;

class BookObserver
{
    public function __construct(
        private readonly AdminActivityLogService $activityLog,
    ) {}

    /**
     * Slug is generated automatically (from the title) and guaranteed to be unique.
     */
    public function creating(Book $book): void
    {
        if (empty($book->slug)) {
            $book->slug = $this->uniqueSlug($book->title);
        }
    }

    public function created(Book $book): void
    {
        $this->activityLog->logChange($book, AdminActivityAction::Created);
    }

    public function updated(Book $book): void
    {
        $this->activityLog->logChange($book, AdminActivityAction::Updated);
    }

    public function deleted(Book $book): void
    {
        $this->activityLog->logChange($book, AdminActivityAction::Deleted);
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
