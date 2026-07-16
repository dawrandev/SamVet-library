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

    protected $fillable = [
        'reader_id', 'source', 'journal_id', 'year',
        'start_month', 'end_month', 'amount',
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
}
