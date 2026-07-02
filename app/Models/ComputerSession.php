<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComputerSession extends Model
{
    protected $fillable = [
        'reader_id', 'date', 'issued_time', 'returned_time',
        'computer_number', 'location', 'purpose', 'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }
}
