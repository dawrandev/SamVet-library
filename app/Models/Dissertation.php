<?php

namespace App\Models;

use App\Enums\CopyCondition;
use App\Enums\DissertationType;
use App\Observers\DissertationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[ObservedBy([DissertationObserver::class])]
class Dissertation extends Model
{
    use HasFactory;

    // `slug` (set by the observer) and `views_count` (DB default) are intentionally
    // NOT fillable — only user-supplied fields belong here.
    protected $fillable = [
        'title', 'author', 'degree',
        'resource_field_id', 'science_field_id', 'doctoral_specialty_id', 'master_specialty_id',
        'advisor', 'institution', 'language_id', 'publication_place_id',
        'defense_year', 'pages', 'udc', 'inventory_number', 'condition',
        'annotation', 'electronic_file',
    ];

    protected function casts(): array
    {
        return [
            'degree' => DissertationType::class,
            'condition' => CopyCondition::class,
            'defense_year' => 'integer',
            'pages' => 'integer',
            'views_count' => 'integer',
        ];
    }

    // --- Relationships ---

    public function resourceField(): BelongsTo
    {
        return $this->belongsTo(ResourceField::class);
    }

    /** "Fan nomi" — PhD/DSc only. */
    public function scienceField(): BelongsTo
    {
        return $this->belongsTo(ScienceField::class);
    }

    /** "Ixtisoslik shifri va nomi" — PhD/DSc only. */
    public function doctoralSpecialty(): BelongsTo
    {
        return $this->belongsTo(DoctoralSpecialty::class);
    }

    /** "Mutaxassislik shifri va nomi" — Magistrlik only. */
    public function masterSpecialty(): BelongsTo
    {
        return $this->belongsTo(MasterSpecialty::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function publicationPlace(): BelongsTo
    {
        return $this->belongsTo(PublicationPlace::class);
    }

    /** Other participants beyond the formal author (muharrir, tarjimon, ...). */
    public function contributors(): MorphMany
    {
        return $this->morphMany(Contributor::class, 'contributable')->orderBy('sort_order');
    }
}
