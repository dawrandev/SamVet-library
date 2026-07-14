<?php

namespace App\Models;

use App\Enums\CopyCondition;
use App\Enums\DissertationDegree;
use App\Observers\AvtoreferatObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([AvtoreferatObserver::class])]
class Avtoreferat extends Model
{
    use HasFactory;

    // `slug` (set by the observer) and `views_count` (DB default) are intentionally
    // NOT fillable — only user-supplied fields belong here.
    protected $fillable = [
        'title', 'author', 'specialty', 'degree', 'council_number',
        'defense_institution', 'performed_institution', 'advisor',
        'udc', 'registration_number', 'condition',
        'publication_place_id', 'publication_year', 'inventory_number',
        'resource_field_id', 'annotation', 'electronic_file',
    ];

    protected function casts(): array
    {
        return [
            'degree' => DissertationDegree::class,
            'condition' => CopyCondition::class,
            'publication_year' => 'integer',
            'views_count' => 'integer',
        ];
    }

    // --- Relationships ---

    public function resourceField(): BelongsTo
    {
        return $this->belongsTo(ResourceField::class);
    }

    public function publicationPlace(): BelongsTo
    {
        return $this->belongsTo(PublicationPlace::class);
    }
}
