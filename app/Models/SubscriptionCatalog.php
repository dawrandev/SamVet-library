<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One journal/newspaper's entry in a given year's official subscription
 * catalog. `is_selected` marks it as part of the library's own shortlist
 * ("ichki katalog") — only those are offered when creating a Subscription.
 */
class SubscriptionCatalog extends Model
{
    use HasFactory;

    protected $table = 'subscription_catalogs';

    protected $fillable = ['year', 'journal_id', 'annual_price', 'is_selected'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'annual_price' => 'decimal:2',
            'is_selected' => 'boolean',
        ];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /** The auto-computed price for a given inclusive month range. */
    public function amountFor(int $startMonth, int $endMonth): float
    {
        $months = $endMonth - $startMonth + 1;

        return round((float) $this->annual_price / 12 * $months, 2);
    }
}
