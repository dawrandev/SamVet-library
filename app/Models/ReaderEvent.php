<?php

namespace App\Models;

use App\Enums\EventRole;
use App\Enums\EventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReaderEvent extends Model
{
    protected $fillable = ['reader_id', 'date', 'name', 'place', 'type', 'role', 'link', 'note'];

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'role' => EventRole::class,
            'date' => 'date',
        ];
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }
}
