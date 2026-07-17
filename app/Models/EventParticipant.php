<?php

namespace App\Models;

use App\Enums\EventRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventParticipant extends Model
{
    protected $fillable = ['event_id', 'reader_id', 'external_name', 'role'];

    protected function casts(): array
    {
        return [
            'role' => EventRole::class,
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    /** The reader's own name, or the free-typed name of an outside guest. */
    public function displayName(): string
    {
        return $this->reader?->full_name ?? (string) $this->external_name;
    }
}
