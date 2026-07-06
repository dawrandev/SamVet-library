<?php

namespace App\Models;

use App\Observers\BookObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

#[ObservedBy([BookObserver::class])]
class Book extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'title', 'slug', 'udc', 'author_mark',
        'book_type_id', 'language_id', 'publisher_id', 'work_id',
        'publication_year', 'publication_place', 'pages', 'isbn', 'print_run', 'annotation',
        'cover_image', 'electronic_file', 'audio_file',
        'views_count',
    ];

    /** Only the publication place is translatable (title/annotation — single language). */
    public array $translatable = ['publication_place'];

    protected function casts(): array
    {
        return [
            'views_count' => 'integer',
            'publication_year' => 'integer',
            'pages' => 'integer',
            'print_run' => 'integer',
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

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
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
}
