<?php

namespace App\Models;

use App\Enums\Month;
use App\Enums\SubscriptionSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single subscription record: a journal/newspaper subscribed for a
 * period (start_month..end_month) of a year, funded either by a specific
 * reader (Foydalanuvchi) or by the branch's own budget — see `source`.
 */
class Subscription extends Model
{
    use HasFactory;

    /**
     * From this year on, subscriptions are catalog-driven: the journal list
     * is narrowed to the official catalog's shortlist, the amount is always
     * computed from the catalog's annual price (never typed by hand), and
     * periods must run consecutively from January with no gaps/overlaps.
     * Earlier years keep the old free-form behavior — those are already
     * done and won't be retroactively corrected.
     */
    public const CATALOG_ENFORCED_FROM_YEAR = 2027;

    protected $fillable = [
        'reader_id', 'source', 'journal_id', 'delivery_location_id', 'post_branch_id', 'year',
        'start_month', 'end_month', 'amount', 'receipt_file',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start_month' => Month::class,
            'end_month' => Month::class,
            'amount' => 'decimal:2',
            'source' => SubscriptionSource::class,
        ];
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(DeliveryLocation::class);
    }

    public function postBranch(): BelongsTo
    {
        return $this->belongsTo(PostBranch::class);
    }
}
