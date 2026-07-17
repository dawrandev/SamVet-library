<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Contributor extends Model
{
    use HasFactory;

    protected $fillable = [
        'contributable_type', 'contributable_id', 'contributor_role_id', 'name', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function contributable(): MorphTo
    {
        return $this->morphTo();
    }

    public function contributorRole(): BelongsTo
    {
        return $this->belongsTo(ContributorRole::class);
    }
}
