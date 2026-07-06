<?php

namespace App\Models;

use App\Enums\Month;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single subscription record: a subscriber subscribed to a
 * journal/newspaper for a period (start_month..end_month) of a year.
 */
class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscriber_id', 'journal_id', 'year',
        'start_month', 'end_month', 'amount',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start_month' => Month::class,
            'end_month' => Month::class,
            'amount' => 'decimal:2',
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}
