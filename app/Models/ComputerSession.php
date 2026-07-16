<?php

namespace App\Models;

use App\Enums\ComputerLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComputerSession extends Model
{
    use HasFactory;

    // Note: issued_at/expires_at/location are set server-side by the Service,
    // never from raw request input — see ComputerSessionData/ComputerSessionService.
    protected $fillable = [
        'reader_id', 'computer_id', 'issued_at', 'returned_at',
        'duration_minutes', 'expires_at', 'location', 'purpose', 'note',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'returned_at' => 'datetime',
            'expires_at' => 'datetime',
            'location' => ComputerLocation::class,
        ];
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }

    /** The "Tugatish" button was clicked. */
    public function isFinished(): bool
    {
        return $this->returned_at !== null;
    }

    /** Allotted time ran out and nobody finished it yet. */
    public function isExpired(): bool
    {
        return ! $this->isFinished() && $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Still running, within its allotted time (or a legacy row with no tracked duration). */
    public function isActive(): bool
    {
        return ! $this->isFinished() && ! $this->isExpired();
    }
}
