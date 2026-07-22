<?php

namespace App\Models;

use App\Observers\BookObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[ObservedBy([BookObserver::class])]
class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'parallel_titles', 'udc', 'author_mark',
        'book_type_id', 'language_id', 'publisher', 'publication_place_id', 'work_id',
        'publication_year', 'pages', 'isbn', 'print_run', 'annotation',
        'target_audience', 'size_cm', 'print_sheets',
        'cover_image', 'electronic_file',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'parallel_titles' => 'array',
            'views_count' => 'integer',
            'publication_year' => 'integer',
            'pages' => 'integer',
            'print_run' => 'integer',
            'size_cm' => 'integer',
        ];
    }

    // --- Relationships ---

    public function type(): BelongsTo
    {
        return $this->belongsTo(BookType::class, 'book_type_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * Every language this book's title (including parallel titles) is
     * written in. `language()`/`language_id` above stays the single
     * "primary" one (the first chosen) for stats and filtering — this is
     * the full set, kept in sync alongside it.
     */
    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'book_language');
    }

    public function publicationPlace(): BelongsTo
    {
        return $this->belongsTo(PublicationPlace::class);
    }

    // Work group (editions in different languages)
    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_author');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'book_category');
    }

    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }

    /** Other participants beyond the formal author (muharrir, tarjimon, ...). */
    public function contributors(): MorphMany
    {
        return $this->morphMany(Contributor::class, 'contributable')->orderBy('sort_order');
    }
}
