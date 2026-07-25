<?php

namespace App\Models;

use App\Enums\ComputerLocation;
use App\Enums\ComputerStatus;
use App\Enums\ComputerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Computer extends Model
{
    use HasFactory;

    protected $fillable = [
        'model', 'type', 'inventory_number', 'computer_number', 'status', 'location', 'note',
    ];

    protected function casts(): array
    {
        return [
            'type' => ComputerType::class,
            'status' => ComputerStatus::class,
            'location' => ComputerLocation::class,
        ];
    }

    // --- Relationships ---

    public function sessions(): HasMany
    {
        return $this->hasMany(ComputerSession::class);
    }

    // --- Helpers ---

    /**
     * Whether an unfinished session currently occupies this computer. Uses
     * the loaded `sessions` relation when available (constrained to
     * whereNull('returned_at') by the caller) to avoid N+1 queries;
     * falls back to a direct query otherwise.
     */
    public function isOccupied(): bool
    {
        return $this->relationLoaded('sessions')
            ? $this->sessions->contains(fn (ComputerSession $s) => $s->returned_at === null)
            : $this->sessions()->whereNull('returned_at')->exists();
    }

    /** Reader-facing status label — hardware condition, or free/occupied when working. */
    public function publicStatusLabel(): string
    {
        if ($this->status !== ComputerStatus::Working) {
            return $this->status->label();
        }

        return $this->isOccupied() ? __('Band') : __('Bo‘sh');
    }

    /** Badge color keyword (success | error | warning) matching publicStatusLabel(). */
    public function publicStatusColor(): string
    {
        if ($this->status !== ComputerStatus::Working) {
            return $this->status->color();
        }

        return $this->isOccupied() ? 'error' : 'success';
    }
}
