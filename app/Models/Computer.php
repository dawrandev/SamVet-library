<?php

namespace App\Models;

use App\Enums\ComputerStatus;
use App\Enums\ComputerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Computer extends Model
{
    use HasFactory;

    protected $fillable = [
        'model', 'type', 'inventory_number', 'status', 'location_id', 'note',
    ];

    protected function casts(): array
    {
        return [
            'type' => ComputerType::class,
            'status' => ComputerStatus::class,
        ];
    }

    // --- Relationships ---

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ComputerSession::class);
    }
}
