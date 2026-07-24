<?php

namespace App\Models;

use App\Enums\CopyCondition;
use App\Enums\DissertationDegree;
use App\Observers\AvtoreferatObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[ObservedBy([AvtoreferatObserver::class])]
class Avtoreferat extends Model
{
    use HasFactory;

    // `slug` (set by the observer) and `views_count` (DB default) are intentionally
    // NOT fillable — only user-supplied fields belong here.
    protected $fillable = [
        'title', 'author', 'specialty', 'science_field_id', 'degree', 'council_number',
        'defense_institution', 'performed_institution', 'advisor',
        'udc', 'registration_number', 'condition',
        'publication_place_id', 'defense_year', 'inventory_number',
        'electronic_file',
    ];

    protected function casts(): array
    {
        return [
            'degree' => DissertationDegree::class,
            'condition' => CopyCondition::class,
            'defense_year' => 'integer',
            'views_count' => 'integer',
        ];
    }

    // --- Relationships ---

    /** "Fan nomi" — same lookup Dissertation uses. */
    public function scienceField(): BelongsTo
    {
        return $this->belongsTo(ScienceField::class);
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
