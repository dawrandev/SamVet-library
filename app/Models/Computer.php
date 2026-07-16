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
}
