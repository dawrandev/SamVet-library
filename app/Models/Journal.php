<?php

namespace App\Models;

use App\Enums\NewspaperType;
use App\Enums\PeriodicityUnit;
use App\Enums\PublicationKind;
use App\Observers\JournalObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([JournalObserver::class])]
class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'kind', 'slug', 'journal_type_id', 'newspaper_type', 'founder',
        'language_id', 'publication_place_id', 'issn', 'index',
        'periodicity_unit', 'periodicity_interval', 'periodicity_count',
    ];

    protected function casts(): array
    {
        return [
            'kind' => PublicationKind::class,
            'newspaper_type' => NewspaperType::class,
            'periodicity_unit' => PeriodicityUnit::class,
            'periodicity_interval' => 'integer',
            'periodicity_count' => 'integer',
        ];
    }

    /**
     * Composes the dynamic unit+interval+count triple into a display string
     * — e.g. "2 haftada 3 marta" — or the named singular ("Haftalik") for
     * the common "once every 1 unit, 1 time" case.
     */
    public function periodicityLabel(): ?string
    {
        if (! $this->periodicity_unit) {
            return null;
        }

        if ($this->periodicity_unit === PeriodicityUnit::Irregular) {
            return __('Muntazam emas');
        }

        $interval = $this->periodicity_interval ?? 1;
        $count = $this->periodicity_count ?? 1;

        if ($interval === 1 && $count === 1) {
            return $this->periodicity_unit->singularLabel();
        }

        return __(':prefix:unit_locative :count marta', [
            'prefix' => $interval > 1 ? "{$interval} " : '',
            'unit_locative' => $this->periodicity_unit->locative(),
            'count' => $count,
        ]);
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
