<?php

namespace App\Models;

use App\Enums\JournalPeriodicity;
use App\Observers\JournalObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

#[ObservedBy([JournalObserver::class])]
class Journal extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name', 'slug', 'journal_type_id', 'founder',
        'language_id', 'publisher_id', 'publication_place',
        'issn', 'index', 'periodicity',
    ];

    /** Only the publication place is translatable (name — single language). */
    public array $translatable = ['publication_place'];

    protected function casts(): array
    {
        return [
            'periodicity' => JournalPeriodicity::class,
        ];
    }

    // --- Relationships ---

    public function type(): BelongsTo
    {
        return $this->belongsTo(JournalType::class, 'journal_type_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(JournalIssue::class)->latest('year');
    }
}
