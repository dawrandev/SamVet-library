<?php

namespace App\Models;

use App\Enums\JournalPeriodicity;
use App\Enums\PublicationKind;
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
        'name', 'kind', 'slug', 'journal_type_id', 'founder',
        'language_id', 'publisher', 'publication_place_id',
        'issn', 'index', 'periodicity', 'periodicity_count',
    ];

    /** Only the publisher is translatable (name — single language). */
    public array $translatable = ['publisher'];

    protected function casts(): array
    {
        return [
            'kind' => PublicationKind::class,
            'periodicity' => JournalPeriodicity::class,
            'periodicity_count' => 'integer',
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

    public function publicationPlace(): BelongsTo
    {
        return $this->belongsTo(PublicationPlace::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(JournalIssue::class)->latest('year');
    }
}
